<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Return permission-aware dashboard metrics, charts, latest orders, and notifications.
     * Revenue figures are strictly omitted unless the staff user has 'finance.view'.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isStaff = $user->roles->contains(fn ($role) => strtolower($role->name) !== 'customer');
        if (! $isStaff) {
            return response()->json(['message' => 'Forbidden. Staff access required.'], 403);
        }

        $canViewFinance = $user->hasRole('super_admin') || $user->can('finance.view');
        $canViewOrders = $user->hasRole('super_admin') || $user->can('orders.view') || $canViewFinance;
        $canViewProducts = $user->hasRole('super_admin') || $user->can('products.view');

        $today = Carbon::today();
        $thirtyDaysAgo = Carbon::today()->subDays(29);

        // 1. Operational KPI Counts
        $todaysOrdersCount = Order::where('created_at', '>=', $today)->count();

        $newCustomersCount = User::role('customer')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $lowStockCount = Product::where('status', 'active')
            ->where('track_stock', true)
            ->whereColumn('stock_qty', '<=', 'low_stock_threshold')
            ->count();

        $openChatsCount = ChatConversation::where('status', 'open')->count();
        $pendingReviewsCount = Review::where('is_approved', false)->count();

        // 2. Finance KPI Cards (Strictly gated by finance.view)
        $revenueStats = null;
        if ($canViewFinance) {
            $paidStatuses = ['paid', 'processing', 'shipped', 'delivered'];

            $todayRevenue = (float) Order::whereIn('status', $paidStatuses)
                ->where('created_at', '>=', $today)
                ->sum('grand_total');

            $monthRevenue = (float) Order::whereIn('status', $paidStatuses)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->sum('grand_total');

            $totalRevenue = (float) Order::whereIn('status', $paidStatuses)
                ->sum('grand_total');

            $paidOrdersCount = Order::whereIn('status', $paidStatuses)->count();
            $avgOrderValue = $paidOrdersCount > 0 ? round($totalRevenue / $paidOrdersCount, 2) : 0.0;

            $revenueStats = [
                'today_revenue' => round($todayRevenue, 2),
                'month_revenue' => round($monthRevenue, 2),
                'total_revenue' => round($totalRevenue, 2),
                'avg_order_value' => $avgOrderValue,
            ];
        }

        // 3. Sales Last 30 Days Chart
        $rawDaily = Order::select(
            DB::raw('DATE(created_at) as day'),
            DB::raw('COUNT(*) as orders_count'),
            DB::raw("SUM(CASE WHEN status IN ('paid','processing','shipped','delivered') THEN grand_total ELSE 0 END) as revenue")
        )
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $salesLast30Days = [];
        for ($i = 29; $i >= 0; $i--) {
            $dateObj = Carbon::today()->subDays($i);
            $dateKey = $dateObj->toDateString();
            $row = $rawDaily->get($dateKey);

            $point = [
                'date' => $dateKey,
                'label' => $dateObj->format('M d'),
                'orders_count' => $row ? (int) $row->orders_count : 0,
            ];

            if ($canViewFinance) {
                $point['revenue'] = $row ? round((float) $row->revenue, 2) : 0.0;
            }

            $salesLast30Days[] = $point;
        }

        // 4. Top Products (Last 30 Days / All Time)
        $topProducts = OrderItem::select(
            'product_id',
            'name_snapshot',
            DB::raw('SUM(qty) as units_sold'),
            DB::raw('SUM(line_total) as total_revenue')
        )
            ->groupBy('product_id', 'name_snapshot')
            ->orderByDesc('units_sold')
            ->take(5)
            ->get()
            ->map(function ($row) use ($canViewFinance) {
                $item = [
                    'product_id' => $row->product_id,
                    'name' => $row->name_snapshot,
                    'units_sold' => (int) $row->units_sold,
                ];
                if ($canViewFinance) {
                    $item['revenue'] = round((float) $row->total_revenue, 2);
                }

                return $item;
            })
            ->values();

        // If no orders yet, fallback to top active products with 0 units sold so the chart is still informative
        if ($topProducts->isEmpty()) {
            $topProducts = Product::where('status', 'active')
                ->take(5)
                ->get()
                ->map(function (Product $p) use ($canViewFinance) {
                    $item = [
                        'product_id' => $p->id,
                        'name' => $p->name,
                        'units_sold' => 0,
                    ];
                    if ($canViewFinance) {
                        $item['revenue'] = 0.0;
                    }

                    return $item;
                });
        }

        // 5. Orders by Status Breakdown
        $statusCounts = Order::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $allStatuses = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        $ordersByStatus = collect($allStatuses)->map(fn (string $st) => [
            'status' => $st,
            'count' => (int) ($statusCounts[$st] ?? 0),
        ])->values();

        // 6. Latest Orders Table
        $latestOrders = [];
        if ($canViewOrders) {
            $latestOrders = Order::with(['user:id,name,email', 'items'])
                ->latest()
                ->take(7)
                ->get()
                ->map(function (Order $o) {
                    $shippingAddr = $o->shipping_address_json ?? [];
                    $customerName = $o->user?->name
                        ?: ($shippingAddr['name'] ?? trim(($shippingAddr['first_name'] ?? '').' '.($shippingAddr['last_name'] ?? '')))
                        ?: 'Guest Customer';
                    $customerEmail = $o->user?->email ?: ($o->guest_email ?: ($shippingAddr['email'] ?? ''));

                    return [
                        'id' => $o->id,
                        'order_number' => $o->order_number,
                        'customer_name' => $customerName,
                        'customer_email' => $customerEmail,
                        'status' => $o->status,
                        'payment_status' => $o->payment_status,
                        'item_count' => (int) $o->items->sum('qty'),
                        'grand_total' => round((float) $o->grand_total, 2),
                        'placed_at' => $o->placed_at ? $o->placed_at->format('M d, Y H:i') : $o->created_at?->format('M d, Y H:i'),
                    ];
                });
        }

        // 7. Low-Stock Products List
        $lowStockItems = [];
        if ($canViewProducts) {
            $lowStockItems = Product::with(['primaryImage'])
                ->where('status', 'active')
                ->where('track_stock', true)
                ->whereColumn('stock_qty', '<=', 'low_stock_threshold')
                ->orderBy('stock_qty')
                ->take(6)
                ->get()
                ->map(fn (Product $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'sku' => $p->sku,
                    'stock' => (int) $p->stock_qty,
                    'low_stock_threshold' => (int) $p->low_stock_threshold,
                    'image' => $p->primaryImage?->thumb_url ?: $p->primaryImage?->url,
                ]);
        }

        // 8. Topbar Notifications Bell Summary
        $notificationItems = [];
        if ($todaysOrdersCount > 0 && $canViewOrders) {
            $notificationItems[] = [
                'id' => 'notif-orders',
                'type' => 'order',
                'title' => "{$todaysOrdersCount} new ".($todaysOrdersCount === 1 ? 'order' : 'orders').' today',
                'subtitle' => 'Review and fulfill incoming customer orders',
                'to' => '/admin/orders',
            ];
        }
        if ($lowStockCount > 0 && $canViewProducts) {
            $notificationItems[] = [
                'id' => 'notif-stock',
                'type' => 'stock',
                'title' => "{$lowStockCount} ".($lowStockCount === 1 ? 'product is' : 'products are').' low on stock',
                'subtitle' => 'Restock items before inventory runs out',
                'to' => '/admin/products',
            ];
        }
        if ($openChatsCount > 0 && ($user->hasRole('super_admin') || $user->can('chat.manage'))) {
            $notificationItems[] = [
                'id' => 'notif-chat',
                'type' => 'chat',
                'title' => "{$openChatsCount} active chat ".($openChatsCount === 1 ? 'conversation' : 'conversations'),
                'subtitle' => 'Visitors waiting for support reply',
                'to' => '/admin/chat',
            ];
        }
        if ($pendingReviewsCount > 0 && ($user->hasRole('super_admin') || $user->can('reviews.manage'))) {
            $notificationItems[] = [
                'id' => 'notif-reviews',
                'type' => 'review',
                'title' => "{$pendingReviewsCount} pending ".($pendingReviewsCount === 1 ? 'review' : 'reviews'),
                'subtitle' => 'Awaiting moderation approval',
                'to' => '/admin/reviews',
            ];
        }

        return response()->json([
            'data' => [
                'cards' => [
                    'todays_orders' => $todaysOrdersCount,
                    'new_customers' => $newCustomersCount,
                    'low_stock_products' => $lowStockCount,
                    'open_chats' => $openChatsCount,
                    'pending_reviews' => $pendingReviewsCount,
                ],
                'revenue' => $revenueStats,
                'charts' => [
                    'sales_last_30_days' => $salesLast30Days,
                    'top_products' => $topProducts,
                    'orders_by_status' => $ordersByStatus,
                ],
                'latest_orders' => $latestOrders,
                'low_stock_items' => $lowStockItems,
                'notifications' => [
                    'unread_count' => count($notificationItems),
                    'new_orders_count' => $todaysOrdersCount,
                    'open_chats_count' => $openChatsCount,
                    'low_stock_count' => $lowStockCount,
                    'pending_reviews_count' => $pendingReviewsCount,
                    'items' => $notificationItems,
                ],
            ],
        ]);
    }
}

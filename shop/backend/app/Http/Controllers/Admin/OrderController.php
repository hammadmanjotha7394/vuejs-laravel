<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * List orders with filtering, searching, sorting, and summary counts.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()
            ->with(['user:id,name,email,phone'])
            ->withCount('items');

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', "%{$term}%")
                    ->orWhere('guest_email', 'like', "%{$term}%")
                    ->orWhere('tracking_number', 'like', "%{$term}%")
                    ->orWhereHas('user', function ($u) use ($term) {
                        $u->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $allowedSorts = ['id', 'order_number', 'grand_total', 'status', 'payment_status', 'created_at', 'placed_at'];
        $sortBy = in_array($request->input('sort_by'), $allowedSorts, true) ? $request->input('sort_by') : 'created_at';
        $sortDir = strtolower((string) $request->input('sort_dir')) === 'asc' ? 'asc' : 'desc';

        $perPage = (int) $request->input('per_page', 20);
        $orders = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        $summary = [
            'total_orders' => Order::count(),
            'unfulfilled_count' => Order::whereIn('status', ['paid', 'processing'])->count(),
            'shipped_count' => Order::where('status', 'shipped')->count(),
            'refunded_count' => Order::where('status', 'refunded')->count(),
            'low_stock_count' => Product::where('track_stock', true)
                ->whereColumn('stock_qty', '<=', 'low_stock_threshold')
                ->count(),
        ];

        return response()->json([
            'data' => $orders->items(),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total(),
            'summary' => $summary,
        ]);
    }

    /**
     * Show full order details including items, addresses, payment details, and status timeline.
     */
    public function show(Order $order): JsonResponse
    {
        $order->load([
            'user:id,name,email,phone,created_at,admin_notes',
            'items.product:id,name,slug',
            'statusHistory' => fn ($q) => $q->with('changer:id,name,email')->latest(),
        ]);

        $customerStats = [
            'orders_count' => 1,
            'total_spent' => (float) $order->grand_total,
        ];

        if ($order->user_id) {
            $customerStats['orders_count'] = Order::where('user_id', $order->user_id)->count();
            $customerStats['total_spent'] = (float) Order::where('user_id', $order->user_id)
                ->whereIn('status', ['paid', 'processing', 'shipped', 'delivered'])
                ->sum('grand_total');
        } elseif ($order->guest_email) {
            $customerStats['orders_count'] = Order::where('guest_email', $order->guest_email)->count();
            $customerStats['total_spent'] = (float) Order::where('guest_email', $order->guest_email)
                ->whereIn('status', ['paid', 'processing', 'shipped', 'delivered'])
                ->sum('grand_total');
        }

        return response()->json([
            'data' => $order,
            'customer_stats' => $customerStats,
        ]);
    }

    /**
     * Change order status with optional note, tracking details, and customer email notification.
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,paid,processing,shipped,delivered,cancelled,refunded',
            'note' => 'nullable|string|max:1000',
            'notify_customer' => 'nullable|boolean',
            'tracking_number' => 'nullable|string|max:120',
            'tracking_url' => 'nullable|string|max:500',
        ]);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        $order->status = $newStatus;

        if ($newStatus === 'paid' && $order->payment_status === 'pending') {
            $order->payment_status = 'paid';
            if (! $order->placed_at) {
                $order->placed_at = now();
            }
        }

        if (array_key_exists('tracking_number', $validated) && $validated['tracking_number'] !== null) {
            $order->tracking_number = $validated['tracking_number'];
        }

        if (array_key_exists('tracking_url', $validated) && $validated['tracking_url'] !== null) {
            $order->tracking_url = $validated['tracking_url'];
        }

        $order->save();

        // Default notify_customer to true when status is shipped/delivered/cancelled unless explicitly false
        $notifyCustomer = array_key_exists('notify_customer', $validated)
            ? (bool) $validated['notify_customer']
            : in_array($newStatus, ['shipped', 'delivered', 'cancelled'], true);

        $historyNote = $validated['note'] ?? "Status updated from {$oldStatus} to {$newStatus}.";
        if ($order->tracking_number && $newStatus === 'shipped' && empty($validated['note'])) {
            $historyNote .= " Tracking: {$order->tracking_number}";
        }

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $newStatus,
            'note' => $historyNote,
            'changed_by' => $request->user()?->id,
        ]);

        ActivityLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => 'order.status_updated',
            'subject_type' => Order::class,
            'subject_id' => $order->id,
            'properties_json' => [
                'order_number' => $order->order_number,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'notify_customer' => $notifyCustomer,
            ],
            'ip' => $request->ip(),
        ]);

        if ($notifyCustomer) {
            $this->sendOrderStatusEmail($order, $newStatus);
        }

        return response()->json([
            'message' => 'Order status updated successfully.',
            'data' => $order->fresh([
                'user:id,name,email,phone,created_at,admin_notes',
                'items.product:id,name,slug',
                'statusHistory' => fn ($q) => $q->with('changer:id,name,email')->latest(),
            ]),
        ]);
    }

    /**
     * Update tracking number / URL for an order, with optional mark-as-shipped shortcut.
     */
    public function updateTracking(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'tracking_number' => 'nullable|string|max:120',
            'tracking_url' => 'nullable|string|max:500',
            'mark_shipped' => 'nullable|boolean',
            'notify_customer' => 'nullable|boolean',
        ]);

        $order->tracking_number = $validated['tracking_number'] ?? null;
        $order->tracking_url = $validated['tracking_url'] ?? null;

        $markShipped = (bool) ($validated['mark_shipped'] ?? false);
        if ($markShipped && $order->status !== 'shipped') {
            $order->status = 'shipped';
        }

        $order->save();

        $note = $order->tracking_number
            ? "Tracking updated: {$order->tracking_number}".($order->tracking_url ? " ({$order->tracking_url})" : '')
            : 'Tracking details cleared.';

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'note' => $note,
            'changed_by' => $request->user()?->id,
        ]);

        $notifyCustomer = array_key_exists('notify_customer', $validated)
            ? (bool) $validated['notify_customer']
            : $markShipped;

        if ($notifyCustomer) {
            $this->sendOrderStatusEmail($order, 'shipped');
        }

        return response()->json([
            'message' => 'Tracking information saved.',
            'data' => $order->fresh([
                'user:id,name,email,phone,created_at,admin_notes',
                'items.product:id,name,slug',
                'statusHistory' => fn ($q) => $q->with('changer:id,name,email')->latest(),
            ]),
        ]);
    }

    /**
     * Save internal staff notes on an order and append to status history.
     */
    public function addNote(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        if (array_key_exists('admin_notes', $validated)) {
            $order->admin_notes = $validated['admin_notes'];
            $order->save();
        }

        if (! empty($validated['note'])) {
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $order->status,
                'note' => '[Internal Note] '.trim($validated['note']),
                'changed_by' => $request->user()?->id,
            ]);
        }

        return response()->json([
            'message' => 'Internal note saved.',
            'data' => $order->fresh([
                'user:id,name,email,phone,created_at,admin_notes',
                'items.product:id,name,slug',
                'statusHistory' => fn ($q) => $q->with('changer:id,name,email')->latest(),
            ]),
        ]);
    }

    /**
     * Resend the order confirmation email to the customer.
     */
    public function resendConfirmation(Request $request, Order $order): JsonResponse
    {
        $recipientEmail = $order->guest_email ?: $order->user?->email;

        if (! $recipientEmail) {
            return response()->json([
                'message' => 'No customer email address is associated with this order.',
            ], 422);
        }

        EmailLog::create([
            'to' => $recipientEmail,
            'subject' => "Order Confirmed — {$order->order_number}",
            'template_key' => 'order_confirmed',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'note' => "Order confirmation email resent to {$recipientEmail}.",
            'changed_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => "Order confirmation email sent to {$recipientEmail}.",
            'data' => $order->fresh([
                'statusHistory' => fn ($q) => $q->with('changer:id,name,email')->latest(),
            ]),
        ]);
    }

    /**
     * List low-stock products and variants for quick inventory management.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with(['category:id,name,slug', 'brand:id,name', 'primaryImage', 'variants'])
            ->where('track_stock', true);

        if ($request->input('filter') === 'out') {
            $query->where('stock_qty', '<=', 0);
        } else {
            $query->whereColumn('stock_qty', '<=', 'low_stock_threshold');
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%");
            });
        }

        $products = $query->orderBy('stock_qty', 'asc')->get();

        return response()->json([
            'data' => $products,
            'alert_email' => (string) SettingService::get('support_email', 'admin@shop.test'),
        ]);
    }

    /**
     * Quick inline update of product or variant stock quantity.
     */
    public function updateStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'nullable|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'stock_qty' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        if (! empty($validated['variant_id'])) {
            $variant = ProductVariant::findOrFail($validated['variant_id']);
            $variant->stock_qty = (int) $validated['stock_qty'];
            $variant->save();

            // Sync parent product total stock from active variants
            $product = Product::find($variant->product_id);
            if ($product) {
                $product->stock_qty = (int) $product->variants()->sum('stock_qty');
                $product->save();
            }

            return response()->json([
                'message' => 'Variant stock updated.',
                'data' => $variant,
            ]);
        }

        if (! empty($validated['product_id'])) {
            $product = Product::findOrFail($validated['product_id']);
            $product->stock_qty = (int) $validated['stock_qty'];
            if (array_key_exists('low_stock_threshold', $validated) && $validated['low_stock_threshold'] !== null) {
                $product->low_stock_threshold = (int) $validated['low_stock_threshold'];
            }
            $product->save();

            ActivityLog::create([
                'actor_user_id' => $request->user()?->id,
                'action' => 'inventory.stock_updated',
                'subject_type' => Product::class,
                'subject_id' => $product->id,
                'properties_json' => [
                    'sku' => $product->sku,
                    'stock_qty' => $product->stock_qty,
                ],
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Product stock updated.',
                'data' => $product,
            ]);
        }

        return response()->json(['message' => 'Either product_id or variant_id is required.'], 422);
    }

    /**
     * Send a low-stock inventory alert email to the configured admin/support address.
     */
    public function sendLowStockAlert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'nullable|email|max:150',
        ]);

        $recipient = $validated['email'] ?? (string) SettingService::get('support_email', 'admin@shop.test');
        $lowStockProducts = Product::where('track_stock', true)
            ->whereColumn('stock_qty', '<=', 'low_stock_threshold')
            ->orderBy('stock_qty', 'asc')
            ->get(['id', 'name', 'sku', 'stock_qty', 'low_stock_threshold']);

        $count = $lowStockProducts->count();

        EmailLog::create([
            'to' => $recipient,
            'subject' => "Low Stock Inventory Alert ({$count} items below threshold)",
            'template_key' => 'low_stock_alert',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return response()->json([
            'message' => "Low-stock alert email for {$count} item(s) sent to {$recipient}.",
            'recipient' => $recipient,
            'items_count' => $count,
        ]);
    }

    /**
     * Helper to record and dispatch customer email notification on order status changes.
     */
    protected function sendOrderStatusEmail(Order $order, string $status): void
    {
        $recipientEmail = $order->guest_email ?: $order->user?->email;
        if (! $recipientEmail) {
            return;
        }

        $map = [
            'shipped' => [
                'template_key' => 'order_shipped',
                'subject' => "Your Order Has Shipped — {$order->order_number}",
            ],
            'delivered' => [
                'template_key' => 'order_delivered',
                'subject' => "Your Order Was Delivered — {$order->order_number}",
            ],
            'cancelled' => [
                'template_key' => 'order_cancelled',
                'subject' => "Order Cancelled — {$order->order_number}",
            ],
            'refunded' => [
                'template_key' => 'order_refunded',
                'subject' => "Refund Processed — {$order->order_number}",
            ],
            'paid' => [
                'template_key' => 'order_confirmed',
                'subject' => "Order Confirmed — {$order->order_number}",
            ],
            'processing' => [
                'template_key' => 'order_processing',
                'subject' => "Your Order Is Being Prepared — {$order->order_number}",
            ],
        ];

        $template = $map[$status] ?? [
            'template_key' => 'order_status_updated',
            'subject' => "Order Status Update — {$order->order_number}",
        ];

        EmailLog::create([
            'to' => $recipientEmail,
            'subject' => $template['subject'],
            'template_key' => $template['template_key'],
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}

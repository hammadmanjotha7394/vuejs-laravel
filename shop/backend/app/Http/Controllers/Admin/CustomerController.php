<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    /**
     * List customers with order count, lifetime spend, search, and status filter.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->baseCustomerQuery($request);

        $allowedSorts = ['id', 'name', 'email', 'orders_count', 'total_spent', 'created_at'];
        $sortBy = in_array($request->input('sort_by'), $allowedSorts, true) ? $request->input('sort_by') : 'created_at';
        $sortDir = strtolower((string) $request->input('sort_dir')) === 'asc' ? 'asc' : 'desc';

        $perPage = (int) $request->input('per_page', 20);
        $customers = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        return response()->json($customers);
    }

    /**
     * Show a customer profile with order history, addresses, reviews, and lifetime stats.
     */
    public function show(User $customer): JsonResponse
    {
        $customer->load([
            'addresses',
            'orders' => fn ($q) => $q->withCount('items')->latest(),
            'reviews' => fn ($q) => $q->with('product:id,name,slug')->latest(),
        ]);

        $paidOrders = $customer->orders->whereIn('status', ['paid', 'processing', 'shipped', 'delivered']);
        $totalSpent = round((float) $paidOrders->sum('grand_total'), 2);
        $ordersCount = $customer->orders->count();
        $avgOrderValue = $paidOrders->count() > 0 ? round($totalSpent / $paidOrders->count(), 2) : 0;

        return response()->json([
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'avatar' => $customer->avatar,
                'is_active' => (bool) $customer->is_active,
                'admin_notes' => $customer->admin_notes,
                'created_at' => $customer->created_at?->toIso8601String(),
                'last_login_at' => $customer->last_login_at?->toIso8601String(),
                'orders_count' => $ordersCount,
                'total_spent' => $totalSpent,
                'average_order_value' => $avgOrderValue,
                'addresses' => $customer->addresses,
                'orders' => $customer->orders,
                'reviews' => $customer->reviews,
            ],
        ]);
    }

    /**
     * Update customer profile notes or active/disabled status.
     */
    public function update(Request $request, User $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:120',
            'phone' => 'nullable|string|max:40',
            'is_active' => 'nullable|boolean',
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        if (array_key_exists('name', $validated)) {
            $customer->name = $validated['name'];
        }
        if (array_key_exists('phone', $validated)) {
            $customer->phone = $validated['phone'];
        }
        if (array_key_exists('is_active', $validated) && $validated['is_active'] !== null) {
            $customer->is_active = (bool) $validated['is_active'];
        }
        if (array_key_exists('admin_notes', $validated)) {
            $customer->admin_notes = $validated['admin_notes'];
        }

        $customer->save();

        ActivityLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => 'customer.updated',
            'subject_type' => User::class,
            'subject_id' => $customer->id,
            'properties_json' => [
                'email' => $customer->email,
                'is_active' => $customer->is_active,
            ],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Customer profile updated.',
            'data' => $customer,
        ]);
    }

    /**
     * Export customers list as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $customers = $this->baseCustomerQuery($request)
            ->orderByDesc('created_at')
            ->get();

        $filename = 'customers-export-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($customers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID',
                'Name',
                'Email',
                'Phone',
                'Status',
                'Orders Count',
                'Total Spent ($)',
                'Last Order Date',
                'Joined Date',
                'Admin Notes',
            ]);

            foreach ($customers as $c) {
                fputcsv($handle, [
                    $c->id,
                    $c->name,
                    $c->email,
                    $c->phone ?? '',
                    $c->is_active ? 'Active' : 'Disabled',
                    (int) $c->orders_count,
                    number_format((float) ($c->total_spent ?? 0), 2, '.', ''),
                    $c->last_order_at ?? '',
                    $c->created_at?->toDateString() ?? '',
                    $c->admin_notes ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Build the base query for customers with aggregated order metrics.
     */
    protected function baseCustomerQuery(Request $request)
    {
        $staffRoles = ['super_admin', 'product_manager', 'order_manager', 'support_agent', 'finance_manager'];

        $query = User::query()
            ->where(function ($q) use ($staffRoles) {
                $q->whereHas('roles', fn ($r) => $r->where('name', 'customer'))
                    ->orWhereDoesntHave('roles', fn ($r) => $r->whereIn('name', $staffRoles));
            })
            ->withCount('orders')
            ->addSelect([
                'total_spent' => Order::selectRaw('COALESCE(SUM(grand_total), 0)')
                    ->whereColumn('user_id', 'users.id')
                    ->whereIn('status', ['paid', 'processing', 'shipped', 'delivered']),
                'last_order_at' => Order::selectRaw('MAX(created_at)')
                    ->whereColumn('user_id', 'users.id'),
            ]);

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        if ($request->filled('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        return $query;
    }
}

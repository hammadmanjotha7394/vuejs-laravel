<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Address;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Review;
use App\Models\Wishlist;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerAccountController extends Controller
{
    /**
     * Get customer account overview (counts, default address, recent orders).
     */
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalOrders = Order::where('user_id', $user->id)->count();
        $activeOrders = Order::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'paid', 'processing', 'shipped'])
            ->count();
        $wishlistCount = Wishlist::where('user_id', $user->id)->count();
        $reviewsCount = Review::where('user_id', $user->id)->count();

        $defaultAddress = Address::where('user_id', $user->id)
            ->orderByDesc('is_default_shipping')
            ->latest()
            ->first();

        $recentOrders = Order::where('user_id', $user->id)
            ->with(['items'])
            ->latest()
            ->take(3)
            ->get()
            ->map(fn (Order $o) => $this->formatOrderListItem($o));

        return response()->json([
            'data' => [
                'stats' => [
                    'total_orders' => $totalOrders,
                    'active_orders' => $activeOrders,
                    'wishlist_count' => $wishlistCount,
                    'reviews_count' => $reviewsCount,
                ],
                'default_address' => $defaultAddress,
                'recent_orders' => $recentOrders,
            ],
        ]);
    }

    /**
     * List all orders belonging strictly to the authenticated customer.
     */
    public function orders(Request $request): JsonResponse
    {
        $user = $request->user();

        $orders = Order::where('user_id', $user->id)
            ->with(['items'])
            ->latest()
            ->paginate(10);

        $orders->getCollection()->transform(fn (Order $o) => $this->formatOrderListItem($o));

        return response()->json($orders);
    }

    /**
     * Get single order details strictly scoped to the authenticated customer.
     * Changing the order number in the URL never reveals another user's order.
     */
    public function orderShow(Request $request, string $orderNumber): JsonResponse
    {
        $user = $request->user();

        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->with(['items.product', 'statusHistory'])
            ->first();

        if (! $order) {
            return response()->json([
                'message' => 'Order not found.',
            ], 404);
        }

        return response()->json([
            'data' => $this->formatOrderDetail($order),
        ]);
    }

    /**
     * Request cancellation of an unshipped order.
     */
    public function requestCancelOrder(Request $request, string $orderNumber): JsonResponse
    {
        $user = $request->user();

        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->first();

        if (! $order) {
            return response()->json([
                'message' => 'Order not found.',
            ], 404);
        }

        if (! in_array($order->status, ['pending', 'paid', 'processing'])) {
            throw ValidationException::withMessages([
                'order' => ["Orders with status '{$order->status}' cannot be cancelled."],
            ]);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $reason = trim($validated['reason']);

        if ($order->status === 'pending' && $order->payment_status !== 'paid') {
            // Unpaid pending order can be cancelled immediately
            $order->status = 'cancelled';
            $order->notes = trim(($order->notes ? $order->notes."\n" : '')."[Cancelled by Customer]: {$reason}");
            $order->save();

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'cancelled',
                'note' => "Cancelled by customer: {$reason}",
                'changed_by' => $user->id,
            ]);

            $message = 'Your unpaid order has been cancelled.';
        } else {
            // Paid/processing unshipped order creates a cancellation request note & notification for admin
            $order->notes = trim(($order->notes ? $order->notes."\n" : '')."[Cancellation Requested by Customer]: {$reason}");
            $order->save();

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $order->status,
                'note' => "Customer requested order cancellation: {$reason}",
                'changed_by' => $user->id,
            ]);

            ActivityLog::create([
                'actor_user_id' => $user->id,
                'action' => 'order.cancel_requested',
                'subject_type' => Order::class,
                'subject_id' => $order->id,
                'properties_json' => [
                    'order_number' => $order->order_number,
                    'reason' => $reason,
                ],
                'ip' => $request->ip(),
            ]);

            $adminEmail = (string) SettingService::get('support_email', 'admin@boutique.com');
            EmailLog::create([
                'to' => $adminEmail,
                'subject' => "Order Cancellation Requested — {$order->order_number}",
                'template_key' => 'order_cancel_request',
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $message = 'Your cancellation request has been submitted to our fulfillment team.';
        }

        $order->load(['items.product', 'statusHistory']);

        return response()->json([
            'message' => $message,
            'data' => $this->formatOrderDetail($order),
        ]);
    }

    /**
     * List saved addresses for the authenticated customer.
     */
    public function addresses(Request $request): JsonResponse
    {
        $user = $request->user();

        $addresses = Address::where('user_id', $user->id)
            ->orderByDesc('is_default_shipping')
            ->latest()
            ->get();

        return response()->json([
            'data' => $addresses,
        ]);
    }

    /**
     * Add a new address for the authenticated customer.
     */
    public function storeAddress(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'label' => 'nullable|string|max:60',
            'name' => 'required|string|max:120',
            'phone' => 'nullable|string|max:40',
            'line1' => 'required|string|max:255',
            'line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'required|string|max:30',
            'country' => 'required|string|max:50',
            'is_default_shipping' => 'nullable|boolean',
            'is_default_billing' => 'nullable|boolean',
        ]);

        $hasAny = Address::where('user_id', $user->id)->exists();
        $isDefaultShipping = (bool) ($validated['is_default_shipping'] ?? ! $hasAny);
        $isDefaultBilling = (bool) ($validated['is_default_billing'] ?? ! $hasAny);

        if ($isDefaultShipping) {
            Address::where('user_id', $user->id)->update(['is_default_shipping' => false]);
        }
        if ($isDefaultBilling) {
            Address::where('user_id', $user->id)->update(['is_default_billing' => false]);
        }

        $address = Address::create([
            'user_id' => $user->id,
            'label' => $validated['label'] ?: 'Home',
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'line1' => $validated['line1'],
            'line2' => $validated['line2'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'] ?? null,
            'postal_code' => $validated['postal_code'],
            'country' => $validated['country'],
            'is_default_shipping' => $isDefaultShipping,
            'is_default_billing' => $isDefaultBilling,
        ]);

        return response()->json([
            'message' => 'Address saved successfully.',
            'data' => $address,
        ], 201);
    }

    /**
     * Update an existing address belonging to the authenticated customer.
     */
    public function updateAddress(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $address = Address::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $validated = $request->validate([
            'label' => 'nullable|string|max:60',
            'name' => 'required|string|max:120',
            'phone' => 'nullable|string|max:40',
            'line1' => 'required|string|max:255',
            'line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'required|string|max:30',
            'country' => 'required|string|max:50',
            'is_default_shipping' => 'nullable|boolean',
            'is_default_billing' => 'nullable|boolean',
        ]);

        if (! empty($validated['is_default_shipping'])) {
            Address::where('user_id', $user->id)->where('id', '!=', $address->id)->update(['is_default_shipping' => false]);
        }
        if (! empty($validated['is_default_billing'])) {
            Address::where('user_id', $user->id)->where('id', '!=', $address->id)->update(['is_default_billing' => false]);
        }

        $address->update([
            'label' => $validated['label'] ?: $address->label,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'line1' => $validated['line1'],
            'line2' => $validated['line2'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'] ?? null,
            'postal_code' => $validated['postal_code'],
            'country' => $validated['country'],
            'is_default_shipping' => isset($validated['is_default_shipping']) ? (bool) $validated['is_default_shipping'] : $address->is_default_shipping,
            'is_default_billing' => isset($validated['is_default_billing']) ? (bool) $validated['is_default_billing'] : $address->is_default_billing,
        ]);

        return response()->json([
            'message' => 'Address updated successfully.',
            'data' => $address->fresh(),
        ]);
    }

    /**
     * Set an address as default shipping & billing.
     */
    public function setDefaultAddress(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $address = Address::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        Address::where('user_id', $user->id)->update([
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ]);

        $address->update([
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ]);

        return response()->json([
            'message' => 'Default address updated.',
            'data' => Address::where('user_id', $user->id)->orderByDesc('is_default_shipping')->latest()->get(),
        ]);
    }

    /**
     * Delete a saved address belonging to the authenticated customer.
     */
    public function destroyAddress(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $address = Address::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $wasDefault = $address->is_default_shipping;
        $address->delete();

        if ($wasDefault) {
            $next = Address::where('user_id', $user->id)->latest()->first();
            $next?->update(['is_default_shipping' => true]);
        }

        return response()->json([
            'message' => 'Address deleted.',
        ]);
    }

    /**
     * List reviews submitted by the authenticated customer.
     */
    public function reviews(Request $request): JsonResponse
    {
        $user = $request->user();

        $reviews = Review::where('user_id', $user->id)
            ->with(['product.primaryImage', 'product.images'])
            ->latest()
            ->get()
            ->map(function (Review $r) {
                $product = $r->product;
                $img = $product?->primaryImage ?: $product?->images?->first();

                return [
                    'id' => $r->id,
                    'rating' => (int) $r->rating,
                    'title' => $r->title,
                    'body' => $r->body,
                    'is_approved' => (bool) $r->is_approved,
                    'helpful_count' => (int) $r->helpful_count,
                    'review_date' => $r->review_date ? $r->review_date->format('M d, Y') : $r->created_at?->format('M d, Y'),
                    'product' => $product ? [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'image' => $img ? ($img->thumb_url ?: $img->url) : null,
                    ] : null,
                ];
            });

        return response()->json([
            'data' => $reviews,
        ]);
    }

    /**
     * Permanently delete the authenticated customer's account after password confirmation.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        if (! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        // Revoke tokens & session
        $user->tokens()->delete();
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // Permanently delete user (orders have nullOnDelete foreign key constraint)
        $user->forceDelete();

        return response()->json([
            'message' => 'Your account has been permanently deleted.',
        ]);
    }

    /**
     * Format order summary for list views.
     *
     * @return array<string, mixed>
     */
    protected function formatOrderListItem(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'subtotal' => (float) $order->subtotal,
            'grand_total' => (float) $order->grand_total,
            'currency' => $order->currency,
            'item_count' => $order->items->sum('qty'),
            'placed_at' => $order->placed_at ? $order->placed_at->format('M d, Y') : $order->created_at?->format('M d, Y'),
            'items_preview' => $order->items->take(4)->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name_snapshot,
                'image' => $item->image_snapshot,
                'qty' => (int) $item->qty,
            ]),
        ];
    }

    /**
     * Format complete order details for single order view & printable invoice.
     *
     * @return array<string, mixed>
     */
    protected function formatOrderDetail(Order $order): array
    {
        $hasCancelRequest = $order->statusHistory
            ->contains(fn ($h) => str_contains(strtolower((string) $h->note), 'requested order cancellation'));

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'has_cancel_request' => $hasCancelRequest,
            'can_request_cancel' => in_array($order->status, ['pending', 'paid', 'processing']) && ! $hasCancelRequest,
            'subtotal' => (float) $order->subtotal,
            'discount_total' => (float) $order->discount_total,
            'shipping_total' => (float) $order->shipping_total,
            'tax_total' => (float) $order->tax_total,
            'grand_total' => (float) $order->grand_total,
            'currency' => $order->currency,
            'coupon_code' => $order->coupon_code,
            'shipping_address' => $order->shipping_address_json,
            'billing_address' => $order->billing_address_json,
            'notes' => $order->notes,
            'tracking_number' => $order->tracking_number,
            'tracking_url' => $order->tracking_url,
            'placed_at' => $order->placed_at ? $order->placed_at->format('M d, Y h:i A') : $order->created_at?->format('M d, Y h:i A'),
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'slug' => $item->product?->slug,
                'name' => $item->name_snapshot,
                'sku' => $item->sku_snapshot,
                'image' => $item->image_snapshot,
                'unit_price' => (float) $item->unit_price,
                'qty' => (int) $item->qty,
                'line_total' => (float) $item->line_total,
            ]),
            'status_history' => $order->statusHistory->map(fn ($h) => [
                'id' => $h->id,
                'status' => $h->status,
                'note' => $h->note,
                'created_at' => $h->created_at?->format('M d, Y h:i A'),
            ]),
        ];
    }
}

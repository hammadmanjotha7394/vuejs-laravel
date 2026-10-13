<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    /**
     * Get saved addresses for the authenticated customer.
     */
    public function addresses(): JsonResponse
    {
        $user = auth('sanctum')->user();
        if (! $user) {
            return response()->json(['data' => []]);
        }

        $addresses = Address::where('user_id', $user->id)
            ->orderByDesc('is_default_shipping')
            ->latest()
            ->get();

        return response()->json([
            'data' => $addresses,
        ]);
    }

    /**
     * Create a PENDING order from the database cart and return the Stripe Checkout Session URL.
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        $validated = $request->validate([
            'email' => 'required|email|max:150',
            'shipping_address' => 'required|array',
            'shipping_address.first_name' => 'required|string|max:100',
            'shipping_address.last_name' => 'required|string|max:100',
            'shipping_address.line1' => 'required|string|max:255',
            'shipping_address.line2' => 'nullable|string|max:255',
            'shipping_address.city' => 'required|string|max:100',
            'shipping_address.state' => 'nullable|string|max:100',
            'shipping_address.postal_code' => 'required|string|max:30',
            'shipping_address.country' => 'required|string|max:50',
            'shipping_address.phone' => 'nullable|string|max:40',
            'billing_address' => 'nullable|array',
            'shipping_rate_id' => 'nullable|integer|exists:shipping_rates,id',
            'notes' => 'nullable|string|max:1000',
            'save_address' => 'nullable|boolean',
            'origin' => 'nullable|url',
        ]);

        $token = $request->header('X-Cart-Token') ?: $request->cookie('boutique_cart_token');
        $cart = CartService::getOrCreateCart($user, $token);

        $result = CheckoutService::createOrderAndSession($cart, $validated, $user);

        return response()->json([
            'message' => 'Checkout session created.',
            'data' => $result,
        ], 201);
    }

    /**
     * Verify an order on the success page using BOTH order_number and stripe_session_id.
     * Never trusts just the order number in the URL.
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order' => 'required|string|max:60',
            'session_id' => 'required|string|max:150',
        ]);

        $order = Order::where('order_number', $validated['order'])
            ->where('stripe_session_id', $validated['session_id'])
            ->with(['items'])
            ->first();

        if (! $order) {
            return response()->json([
                'message' => 'Order verification failed. Invalid session or order reference.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'guest_email' => $order->guest_email,
                'subtotal' => (float) $order->subtotal,
                'discount_total' => (float) $order->discount_total,
                'shipping_total' => (float) $order->shipping_total,
                'tax_total' => (float) $order->tax_total,
                'grand_total' => (float) $order->grand_total,
                'currency' => $order->currency,
                'coupon_code' => $order->coupon_code,
                'shipping_address' => $order->shipping_address_json,
                'notes' => $order->notes,
                'placed_at' => $order->placed_at ? $order->placed_at->format('M d, Y h:i A') : $order->created_at->format('M d, Y h:i A'),
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->name_snapshot,
                    'sku' => $item->sku_snapshot,
                    'image' => $item->image_snapshot,
                    'unit_price' => (float) $item->unit_price,
                    'qty' => (int) $item->qty,
                    'line_total' => (float) $item->line_total,
                ]),
            ],
        ]);
    }

    /**
     * Refund an order from Admin (Full or Partial).
     */
    public function refund(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
        ]);

        $admin = $request->user();
        $updated = CheckoutService::refundOrder(
            $order,
            isset($validated['amount']) ? (float) $validated['amount'] : null,
            $validated['reason'] ?? null,
            $admin
        );

        return response()->json([
            'message' => 'Order refunded successfully.',
            'data' => $updated,
        ]);
    }
}

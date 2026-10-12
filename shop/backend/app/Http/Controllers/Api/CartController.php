<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Resolve cart from request token or authenticated user.
     */
    protected function resolveCart(Request $request): Cart
    {
        $user = auth('sanctum')->user();
        $token = $request->header('X-Cart-Token') ?: $request->cookie('boutique_cart_token');

        return CartService::getOrCreateCart($user, $token);
    }

    /**
     * Wrap cart response and attach cookie if guest.
     */
    protected function cartResponse(Cart $cart, array $data, int $status = 200): JsonResponse
    {
        $response = response()->json([
            'data' => $data,
        ], $status);

        if (! auth('sanctum')->check()) {
            $response->cookie('boutique_cart_token', $cart->token, 60 * 24 * 30, null, null, false, false);
        }

        return $response;
    }

    /**
     * Retrieve the current cart with verified prices, inventory, and promotions.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user();
        $cart = $this->resolveCart($request);
        $data = CartService::formatCart($cart, $user);

        return $this->cartResponse($cart, $data);
    }

    /**
     * Add an item to the cart.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'quantity' => 'nullable|integer|min:1|max:99',
        ]);

        $user = auth('sanctum')->user();
        $cart = $this->resolveCart($request);
        $quantity = (int) ($validated['quantity'] ?? 1);

        $data = CartService::addItem($cart, (int) $validated['product_id'], $validated['variant_id'] ?? null, $quantity, $user);

        return $this->cartResponse($cart, $data, 201);
    }

    /**
     * Update an item's quantity in the cart.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0|max:99',
        ]);

        $user = auth('sanctum')->user();
        $cart = $this->resolveCart($request);

        $data = CartService::updateItem($cart, $id, (int) $validated['quantity'], $user);

        return $this->cartResponse($cart, $data);
    }

    /**
     * Remove an item from the cart.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = auth('sanctum')->user();
        $cart = $this->resolveCart($request);

        $data = CartService::removeItem($cart, $id, $user);

        return $this->cartResponse($cart, $data);
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user();
        $cart = $this->resolveCart($request);

        $data = CartService::clearCart($cart, $user);

        return $this->cartResponse($cart, $data);
    }

    /**
     * Apply a coupon code to the cart.
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $user = auth('sanctum')->user();
        $cart = $this->resolveCart($request);

        $data = CartService::applyCoupon($cart, $validated['code'], $user);

        return $this->cartResponse($cart, $data);
    }

    /**
     * Remove the active coupon code from the cart.
     */
    public function removeCoupon(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user();
        $cart = $this->resolveCart($request);

        $data = CartService::removeCoupon($cart, $user);

        return $this->cartResponse($cart, $data);
    }

    /**
     * Merge a guest cart into the authenticated user's cart on login.
     */
    public function merge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'guest_token' => 'required|string|max:100',
        ]);

        $user = auth('sanctum')->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $cart = CartService::getOrCreateCart($user);
        CartService::mergeGuestCart($cart, $validated['guest_token']);
        $data = CartService::formatCart($cart, $user);

        return $this->cartResponse($cart, $data);
    }
}

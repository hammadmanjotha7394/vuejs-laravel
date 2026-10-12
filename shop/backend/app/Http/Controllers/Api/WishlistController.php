<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /**
     * List wishlisted products for authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user();
        if (! $user) {
            return response()->json(['data' => []]);
        }

        $productIds = Wishlist::where('user_id', $user->id)
            ->latest()
            ->pluck('product_id');

        $products = Product::whereIn('id', $productIds)
            ->whereIn('status', ['active', 'published'])
            ->with(['primaryImage', 'images', 'brand', 'category'])
            ->get()
            ->map(fn (Product $p) => ProductService::formatProductCard($p));

        return response()->json([
            'data' => $products,
        ]);
    }

    /**
     * Toggle product in user's wishlist.
     */
    public function toggle(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $productId = (int) $validated['product_id'];

        $existing = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();
            $saved = false;
            $message = 'Removed from your wishlist.';
        } else {
            Wishlist::create([
                'user_id' => $user->id,
                'product_id' => $productId,
            ]);
            $saved = true;
            $message = 'Saved to your wishlist.';
        }

        return response()->json([
            'saved' => $saved,
            'message' => $message,
            'count' => Wishlist::where('user_id', $user->id)->count(),
        ]);
    }

    /**
     * Sync guest wishlist product IDs from localStorage on login.
     */
    public function sync(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        foreach ($validated['product_ids'] as $productId) {
            Wishlist::firstOrCreate([
                'user_id' => $user->id,
                'product_id' => (int) $productId,
            ]);
        }

        $productIds = Wishlist::where('user_id', $user->id)->pluck('product_id');

        $products = Product::whereIn('id', $productIds)
            ->whereIn('status', ['active', 'published'])
            ->with(['primaryImage', 'images', 'brand', 'category'])
            ->get()
            ->map(fn (Product $p) => ProductService::formatProductCard($p));

        return response()->json([
            'data' => $products,
            'count' => $products->count(),
        ]);
    }

    /**
     * Remove a product from the wishlist.
     */
    public function destroy(int $productId): JsonResponse
    {
        $user = auth('sanctum')->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->delete();

        return response()->json([
            'message' => 'Item removed from wishlist.',
            'count' => Wishlist::where('user_id', $user->id)->count(),
        ]);
    }
}

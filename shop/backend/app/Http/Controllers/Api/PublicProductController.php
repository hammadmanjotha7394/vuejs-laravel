<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Services\ProductService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicProductController extends Controller
{
    /**
     * Filter and paginate products for storefront listing.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 20);
        $products = ProductService::filterProducts($request->all(), $perPage);

        return response()->json($products);
    }

    /**
     * Retrieve single product with full specifications, sections, images, and variants.
     */
    public function show(string $slug): JsonResponse
    {
        $details = ProductService::getProductDetails($slug);

        if (! $details) {
            return response()->json([
                'message' => "Product '{$slug}' not found.",
            ], 404);
        }

        return response()->json([
            'data' => $details,
        ]);
    }

    /**
     * Fast search suggestion endpoint for live search overlay (max 6 items).
     */
    public function suggest(Request $request): JsonResponse
    {
        $query = (string) $request->input('q', '');
        $suggestions = ProductService::getSearchSuggestions($query);

        return response()->json([
            'data' => $suggestions,
        ]);
    }

    /**
     * Available catalog facets/filters for sidebar.
     */
    public function filters(): JsonResponse
    {
        $filters = ProductService::getCatalogFilters();

        return response()->json([
            'data' => $filters,
        ]);
    }

    /**
     * Submit a customer review for a product.
     */
    public function storeReview(Request $request, int|string $id): JsonResponse
    {
        $product = Product::where('id', $id)->orWhere('slug', $id)->firstOrFail();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'required|string|max:150',
            'body' => 'required|string|max:2000',
            'author_name' => 'nullable|string|max:100',
        ]);

        $user = auth('sanctum')->user();
        $authorName = $validated['author_name'] ?: ($user ? $user->name : 'Verified Customer');

        $moderationOn = (bool) SettingService::get('review_moderation', false);

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $user?->id,
            'author_name' => $authorName,
            'rating' => $validated['rating'],
            'title' => $validated['title'],
            'body' => $validated['body'],
            'is_approved' => ! $moderationOn,
            'is_admin_created' => false,
            'review_date' => now(),
            'helpful_count' => 0,
        ]);

        return response()->json([
            'message' => $moderationOn
                ? 'Thank you! Your review has been submitted and will appear shortly.'
                : 'Thank you! Your review has been published.',
            'data' => [
                'id' => $review->id,
                'author_name' => $review->author_name,
                'rating' => (int) $review->rating,
                'title' => $review->title,
                'body' => $review->body,
                'review_date' => $review->review_date ? $review->review_date->format('M d, Y') : 'Just now',
                'helpful_count' => 0,
            ],
        ], 201);
    }

    /**
     * Increment helpful vote count on a review.
     */
    public function voteHelpful(int $id): JsonResponse
    {
        $review = Review::where('id', $id)->where('is_approved', true)->firstOrFail();
        $review->increment('helpful_count');

        return response()->json([
            'message' => 'Thank you for your feedback.',
            'helpful_count' => $review->helpful_count,
        ]);
    }
}

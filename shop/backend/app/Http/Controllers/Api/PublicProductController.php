<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Services\ImageService;
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
     * Submit a customer review for a product (with optional photos).
     */
    public function storeReview(Request $request, int|string $id): JsonResponse
    {
        $product = Product::where('id', $id)->orWhere('slug', $id)->firstOrFail();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'required|string|max:150',
            'body' => 'required|string|max:2000',
            'author_name' => 'nullable|string|max:100',
            'photos' => 'nullable|array|max:4',
            'photos.*' => 'string|max:500',
        ]);

        $user = $request->user();
        $authorName = strip_tags(($validated['author_name'] ?? null) ?: ($user ? $user->name : 'Verified Customer'));

        $moderationOn = (bool) SettingService::get('review_moderation', false);

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $user?->id,
            'author_name' => $authorName,
            'rating' => $validated['rating'],
            'title' => strip_tags($validated['title']),
            'body' => strip_tags($validated['body']),
            'photos_json' => $validated['photos'] ?? [],
            'is_approved' => ! $moderationOn,
            'is_admin_created' => false,
            'review_date' => now(),
            'helpful_count' => 0,
        ]);

        $ratingSummary = Review::recalculateProductRating($product->id);

        return response()->json([
            'message' => $moderationOn
                ? 'Thank you! Your review has been submitted and is awaiting moderation.'
                : 'Thank you! Your review has been published.',
            'data' => $review->is_approved ? Review::formatForPublic($review) : null,
            'is_approved' => (bool) $review->is_approved,
            'rating_summary' => $ratingSummary,
        ], 201);
    }

    /**
     * Upload an optional photo for a customer review.
     */
    public function uploadReviewPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $processed = ImageService::processProductImage($request->file('image'), 'reviews');

        return response()->json([
            'url' => $processed['medium_url'] ?: $processed['large_url'],
            'thumb_url' => $processed['thumb_url'],
        ]);
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

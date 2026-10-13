<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Review;
use App\Services\ImageService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * List all reviews with admin-only visibility of `is_admin_created` and `created_by_admin_id`.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Review::query()->with([
            'product:id,name,slug,sku',
            'product.primaryImage',
        ]);

        if ($request->filled('status')) {
            if ($request->input('status') === 'pending') {
                $query->where('is_approved', false);
            } elseif ($request->input('status') === 'approved') {
                $query->where('is_approved', true);
            }
        }

        if ($request->filled('rating') && (int) $request->input('rating') >= 1 && (int) $request->input('rating') <= 5) {
            $query->where('rating', (int) $request->input('rating'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->input('product_id'));
        }

        if ($request->filled('source')) {
            if ($request->input('source') === 'admin') {
                $query->where('is_admin_created', true);
            } elseif ($request->input('source') === 'customer') {
                $query->where('is_admin_created', false);
            }
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('author_name', 'like', "%{$term}%")
                    ->orWhere('title', 'like', "%{$term}%")
                    ->orWhere('body', 'like', "%{$term}%")
                    ->orWhereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$term}%"));
            });
        }

        $perPage = min(max((int) $request->input('per_page', 20), 5), 100);
        $paginator = $query->orderByDesc('review_date')->orderByDesc('id')->paginate($perPage);

        // Explicitly reveal hidden admin-only fields for the admin panel
        $paginator->getCollection()->each(function (Review $review) {
            $review->makeVisible(['is_admin_created', 'created_by_admin_id', 'user_id']);
        });

        $stats = [
            'total' => Review::count(),
            'pending' => Review::where('is_approved', false)->count(),
            'approved' => Review::where('is_approved', true)->count(),
            'admin_created' => Review::where('is_admin_created', true)->count(),
        ];

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Create review(s) as an admin for one or multiple products with custom reviewer name, avatar/initials, and date.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'nullable|integer|exists:products,id',
            'product_ids' => 'nullable|array|min:1',
            'product_ids.*' => 'integer|exists:products,id',
            'author_name' => 'required|string|max:120',
            'author_avatar' => 'nullable|string|max:500',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:180',
            'body' => 'required|string|max:4000',
            'photos' => 'nullable|array|max:6',
            'photos.*' => 'string|max:500',
            'review_date' => 'nullable|date',
            'is_approved' => 'nullable|boolean',
            'admin_reply' => 'nullable|string|max:2000',
        ]);

        $productIds = ! empty($validated['product_ids'])
            ? array_unique($validated['product_ids'])
            : (! empty($validated['product_id']) ? [(int) $validated['product_id']] : []);

        if (empty($productIds)) {
            return response()->json([
                'message' => 'Please select at least one product for this review.',
            ], 422);
        }

        $reviewDate = ! empty($validated['review_date'])
            ? Carbon::parse($validated['review_date'])
            : now();

        $isApproved = array_key_exists('is_approved', $validated)
            ? (bool) $validated['is_approved']
            : true;

        $createdReviews = [];

        foreach ($productIds as $pid) {
            $review = Review::create([
                'product_id' => $pid,
                'user_id' => null,
                'author_name' => strip_tags($validated['author_name']),
                'author_avatar' => $validated['author_avatar'] ?? null,
                'rating' => (int) $validated['rating'],
                'title' => isset($validated['title']) ? strip_tags($validated['title']) : null,
                'body' => strip_tags($validated['body']),
                'photos_json' => $validated['photos'] ?? [],
                'admin_reply' => ! empty($validated['admin_reply']) ? strip_tags($validated['admin_reply']) : null,
                'admin_replied_at' => ! empty($validated['admin_reply']) ? now() : null,
                'is_approved' => $isApproved,
                'is_admin_created' => true,
                'created_by_admin_id' => $request->user()?->id,
                'review_date' => $reviewDate,
                'helpful_count' => 0,
            ]);

            $review->makeVisible(['is_admin_created', 'created_by_admin_id']);
            $createdReviews[] = $review;

            Review::recalculateProductRating((int) $pid);
        }

        ActivityLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => 'review.admin_created',
            'subject_type' => Review::class,
            'subject_id' => $createdReviews[0]->id,
            'properties_json' => [
                'author_name' => $validated['author_name'],
                'rating' => $validated['rating'],
                'product_ids' => $productIds,
            ],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => count($createdReviews) > 1
                ? count($createdReviews).' reviews created and published.'
                : 'Review created and published.',
            'data' => count($createdReviews) === 1 ? $createdReviews[0] : $createdReviews,
        ], 201);
    }

    /**
     * Update an existing review (edit fields, approve/reject, or reply).
     */
    public function update(Request $request, Review $review): JsonResponse
    {
        $validated = $request->validate([
            'author_name' => 'sometimes|required|string|max:120',
            'author_avatar' => 'nullable|string|max:500',
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'title' => 'nullable|string|max:180',
            'body' => 'sometimes|required|string|max:4000',
            'photos' => 'nullable|array|max:6',
            'photos.*' => 'string|max:500',
            'review_date' => 'nullable|date',
            'is_approved' => 'nullable|boolean',
            'admin_reply' => 'nullable|string|max:2000',
        ]);

        if (isset($validated['author_name'])) {
            $validated['author_name'] = strip_tags($validated['author_name']);
        }
        if (array_key_exists('title', $validated) && $validated['title'] !== null) {
            $validated['title'] = strip_tags($validated['title']);
        }
        if (isset($validated['body'])) {
            $validated['body'] = strip_tags($validated['body']);
        }
        if (array_key_exists('photos', $validated)) {
            $validated['photos_json'] = $validated['photos'] ?? [];
            unset($validated['photos']);
        }
        if (! empty($validated['review_date'])) {
            $validated['review_date'] = Carbon::parse($validated['review_date']);
        }
        if (array_key_exists('admin_reply', $validated)) {
            $validated['admin_reply'] = $validated['admin_reply'] ? strip_tags($validated['admin_reply']) : null;
            $validated['admin_replied_at'] = $validated['admin_reply'] ? now() : null;
        }

        $review->update($validated);
        Review::recalculateProductRating((int) $review->product_id);

        $fresh = $review->fresh(['product:id,name,slug,sku']);
        $fresh->makeVisible(['is_admin_created', 'created_by_admin_id', 'user_id']);

        return response()->json([
            'message' => 'Review updated successfully.',
            'data' => $fresh,
        ]);
    }

    /**
     * Quick approve / reject status update.
     */
    public function updateStatus(Request $request, Review $review): JsonResponse
    {
        $validated = $request->validate([
            'is_approved' => 'required|boolean',
        ]);

        $review->update(['is_approved' => (bool) $validated['is_approved']]);
        Review::recalculateProductRating((int) $review->product_id);

        $review->makeVisible(['is_admin_created', 'created_by_admin_id', 'user_id']);

        return response()->json([
            'message' => $review->is_approved ? 'Review approved.' : 'Review unapproved.',
            'data' => $review,
        ]);
    }

    /**
     * Add or update public store reply on a review.
     */
    public function reply(Request $request, Review $review): JsonResponse
    {
        $validated = $request->validate([
            'admin_reply' => 'nullable|string|max:2000',
        ]);

        $reply = ! empty($validated['admin_reply']) ? strip_tags($validated['admin_reply']) : null;

        $review->update([
            'admin_reply' => $reply,
            'admin_replied_at' => $reply ? now() : null,
        ]);

        Review::recalculateProductRating((int) $review->product_id);

        $review->makeVisible(['is_admin_created', 'created_by_admin_id', 'user_id']);

        return response()->json([
            'message' => $reply ? 'Store reply saved.' : 'Store reply removed.',
            'data' => $review,
        ]);
    }

    /**
     * Delete a review and recalculate product rating average & count.
     */
    public function destroy(Review $review): JsonResponse
    {
        $productId = (int) $review->product_id;
        $review->delete();

        Review::recalculateProductRating($productId);

        return response()->json([
            'message' => 'Review deleted.',
        ]);
    }

    /**
     * Bulk import reviews from CSV.
     * Columns supported: product_slug (or product_id / sku), author_name, author_avatar, rating, title, body, review_date, is_approved
     */
    public function importCsv(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:4096',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if (! $handle) {
            return response()->json(['message' => 'Unable to read CSV file.'], 422);
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);

            return response()->json(['message' => 'CSV file is empty.'], 422);
        }

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);
        $importedCount = 0;
        $affectedProducts = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }
            $data = [];
            foreach ($headers as $idx => $col) {
                $data[$col] = isset($row[$idx]) ? trim((string) $row[$idx]) : '';
            }

            $product = null;
            if (! empty($data['product_id'])) {
                $product = Product::find((int) $data['product_id']);
            }
            if (! $product && ! empty($data['product_slug'])) {
                $product = Product::where('slug', $data['product_slug'])->first();
            }
            if (! $product && ! empty($data['sku'])) {
                $product = Product::where('sku', $data['sku'])->first();
            }

            if (! $product || empty($data['author_name']) || empty($data['body'])) {
                continue;
            }

            $rating = isset($data['rating']) ? max(1, min(5, (int) $data['rating'])) : 5;
            $reviewDate = ! empty($data['review_date']) ? Carbon::parse($data['review_date']) : now();
            $isApproved = isset($data['is_approved']) && $data['is_approved'] !== ''
                ? filter_var($data['is_approved'], FILTER_VALIDATE_BOOLEAN)
                : true;

            Review::create([
                'product_id' => $product->id,
                'user_id' => null,
                'author_name' => strip_tags($data['author_name']),
                'author_avatar' => $data['author_avatar'] ?? null,
                'rating' => $rating,
                'title' => ! empty($data['title']) ? strip_tags($data['title']) : null,
                'body' => strip_tags($data['body']),
                'is_approved' => $isApproved,
                'is_admin_created' => true,
                'created_by_admin_id' => $request->user()?->id,
                'review_date' => $reviewDate,
                'helpful_count' => 0,
            ]);

            $affectedProducts[$product->id] = true;
            $importedCount++;
        }

        fclose($handle);

        foreach (array_keys($affectedProducts) as $pid) {
            Review::recalculateProductRating((int) $pid);
        }

        return response()->json([
            'message' => "Successfully imported {$importedCount} reviews.",
            'imported_count' => $importedCount,
        ]);
    }

    /**
     * Upload a review photo or reviewer avatar in admin.
     */
    public function uploadPhoto(Request $request): JsonResponse
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
}

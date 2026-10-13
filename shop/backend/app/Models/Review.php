<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'author_name',
        'author_avatar',
        'rating',
        'title',
        'body',
        'photos_json',
        'admin_reply',
        'admin_replied_at',
        'is_approved',
        'is_admin_created',
        'created_by_admin_id',
        'review_date',
        'helpful_count',
    ];

    /**
     * Hidden fields to ensure admin-created reviews are indistinguishable from customer reviews in public APIs.
     */
    protected $hidden = [
        'is_admin_created',
        'created_by_admin_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'photos_json' => 'array',
            'is_approved' => 'boolean',
            'is_admin_created' => 'boolean',
            'rating' => 'integer',
            'helpful_count' => 'integer',
            'review_date' => 'datetime',
            'admin_replied_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function adminCreator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    /**
     * Format a review for public storefront consumption.
     * Strictly omits any admin-identifying or internal user flags.
     *
     * @return array<string, mixed>
     */
    public static function formatForPublic(self $review): array
    {
        return [
            'id' => $review->id,
            'author_name' => $review->author_name,
            'author_avatar' => $review->author_avatar,
            'rating' => (int) $review->rating,
            'title' => $review->title,
            'body' => $review->body,
            'photos' => array_values(array_filter((array) ($review->photos_json ?? []))),
            'admin_reply' => $review->admin_reply,
            'admin_replied_at' => $review->admin_replied_at
                ? $review->admin_replied_at->format('M d, Y')
                : null,
            'review_date' => $review->review_date
                ? $review->review_date->format('M d, Y')
                : ($review->created_at ? $review->created_at->format('M d, Y') : null),
            'helpful_count' => (int) $review->helpful_count,
        ];
    }

    /**
     * Recalculate and cache a product's rating average, count, and breakdown.
     *
     * @return array{average: float, count: int, breakdown: array<int, int>}
     */
    public static function recalculateProductRating(int $productId): array
    {
        $approved = self::where('product_id', $productId)
            ->where('is_approved', true)
            ->select('rating')
            ->get();

        $count = $approved->count();
        $average = $count > 0 ? round((float) $approved->avg('rating'), 1) : 0.0;

        $breakdown = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($approved as $rev) {
            $r = (int) $rev->rating;
            if (isset($breakdown[$r])) {
                $breakdown[$r]++;
            }
        }

        $summary = [
            'average' => $average,
            'count' => $count,
            'breakdown' => $breakdown,
        ];

        Cache::put("product_rating_summary_{$productId}", $summary, 3600);
        Cache::forget('storefront_featured_reviews');

        return $summary;
    }
}

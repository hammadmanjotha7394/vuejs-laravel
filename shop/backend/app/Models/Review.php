<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'is_admin_created' => 'boolean',
            'rating' => 'integer',
            'helpful_count' => 'integer',
            'review_date' => 'datetime',
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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_id',
        'path',
        'thumb_path',
        'medium_path',
        'large_path',
        'external_url',
        'alt',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['url', 'thumb_url', 'medium_url', 'large_url'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function getUrlAttribute(): ?string
    {
        if (! empty($this->external_url)) {
            return $this->external_url;
        }

        if (! empty($this->path)) {
            return filter_var($this->path, FILTER_VALIDATE_URL) ? $this->path : asset('storage/'.ltrim($this->path, '/'));
        }

        return null;
    }

    public function getThumbUrlAttribute(): ?string
    {
        if (! empty($this->thumb_path)) {
            return asset('storage/'.ltrim($this->thumb_path, '/'));
        }

        return $this->url;
    }

    public function getMediumUrlAttribute(): ?string
    {
        if (! empty($this->medium_path)) {
            return asset('storage/'.ltrim($this->medium_path, '/'));
        }

        return $this->url;
    }

    public function getLargeUrlAttribute(): ?string
    {
        if (! empty($this->large_path)) {
            return asset('storage/'.ltrim($this->large_path, '/'));
        }

        return $this->url;
    }
}

<?php

namespace App\Models;

use App\Services\EmailService;
use App\Services\SettingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'status',
        'is_featured',
        'base_price',
        'compare_at_price',
        'cost_price',
        'track_stock',
        'stock_qty',
        'low_stock_threshold',
        'allow_backorder',
        'weight',
        'length',
        'width',
        'height',
        'tax_class',
        'seo_title',
        'seo_description',
        'seo_social_image',
        'video_url',
        'video_path',
        'video_poster',
        'related_products_json',
        'frequently_bought_together_json',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'track_stock' => 'boolean',
            'allow_backorder' => 'boolean',
            'base_price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'stock_qty' => 'integer',
            'low_stock_threshold' => 'integer',
            'related_products_json' => 'array',
            'frequently_bought_together_json' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->ofMany(['sort_order' => 'min'], function ($query) {
            $query->where('is_primary', true);
        });
    }

    public function optionTypes(): HasMany
    {
        return $this->hasMany(ProductOptionType::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function detailsSections(): HasMany
    {
        return $this->hasMany(ProductDetailsSection::class)->orderBy('sort_order');
    }

    public function specs(): HasMany
    {
        return $this->hasMany(ProductSpec::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    protected $appends = [
        'display_badge',
    ];

    protected static function booted(): void
    {
        static::updating(function (Product $product) {
            if ($product->isDirty('slug') && ! empty($product->getOriginal('slug'))) {
                $oldSlug = $product->getOriginal('slug');
                $newSlug = $product->slug;
                if ($oldSlug !== $newSlug && Schema::hasTable('url_redirects')) {
                    UrlRedirect::updateOrCreate(
                        ['source_path' => '/products/'.$oldSlug],
                        [
                            'target_path' => '/products/'.$newSlug,
                            'status_code' => 301,
                            'is_active' => true,
                        ]
                    );
                }
            }
        });

        static::updated(function (Product $product) {
            if ($product->wasChanged('stock_qty') && (int) $product->getOriginal('stock_qty') <= 0 && (int) $product->stock_qty > 0) {
                if (Schema::hasTable('back_in_stock_subscriptions')) {
                    $subs = BackInStockSubscription::where('product_id', $product->id)
                        ->whereNull('notified_at')
                        ->get();

                    $symbol = (string) SettingService::get('currency_symbol', '$');
                    $siteUrl = rtrim((string) config('app.url', 'http://localhost'), '/');

                    foreach ($subs as $sub) {
                        EmailService::sendTemplate('back_in_stock', $sub->email, [
                            'product_name' => $product->name,
                            'product_price' => $symbol.number_format((float) $product->base_price, 2),
                            'product_url' => $siteUrl.'/products/'.$product->slug,
                        ]);
                        $sub->update(['notified_at' => now()]);
                    }
                }
            }
        });
    }

    public function getDisplayBadgeAttribute(): ?string
    {
        if ($this->relationLoaded('tags') && $this->tags->isNotEmpty()) {
            return $this->tags->first()->name;
        }

        if ($this->track_stock && (int) $this->stock_qty <= 0 && ! $this->allow_backorder) {
            return 'Sold out';
        }

        if ($this->track_stock && (int) $this->stock_qty > 0 && (int) $this->stock_qty <= (int) ($this->low_stock_threshold ?: 5)) {
            return 'Low stock';
        }

        if ($this->compare_at_price && (float) $this->compare_at_price > (float) $this->base_price) {
            return 'Sale';
        }

        if ($this->is_featured) {
            return 'Best seller';
        }

        if ($this->created_at && $this->created_at->gt(now()->subDays(14))) {
            return 'New';
        }

        return null;
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }
}

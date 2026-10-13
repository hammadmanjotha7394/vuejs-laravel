<?php

namespace App\Models;

use App\Services\CategoryService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'icon',
        'sort_order',
        'is_active',
        'is_featured',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Category $category) {
            if ($category->isDirty('slug') && ! empty($category->getOriginal('slug'))) {
                $oldSlug = $category->getOriginal('slug');
                $newSlug = $category->slug;
                if ($oldSlug !== $newSlug && Schema::hasTable('url_redirects')) {
                    UrlRedirect::updateOrCreate(
                        ['source_path' => '/category/'.$oldSlug],
                        [
                            'target_path' => '/category/'.$newSlug,
                            'status_code' => 301,
                            'is_active' => true,
                        ]
                    );
                }
            }
        });

        static::saved(function () {
            CategoryService::clearCache();
        });

        static::deleted(function () {
            CategoryService::clearCache();
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }

    public function directProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}

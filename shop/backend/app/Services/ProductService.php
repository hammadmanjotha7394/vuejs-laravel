<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDetailsSection;
use App\Models\ProductImage;
use App\Models\ProductOptionValue;
use App\Models\ProductSpec;
use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * Filter and paginate products for public storefront with optimized card payload.
     */
    public static function filterProducts(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::query()
            ->whereIn('status', ['active', 'published'])
            ->with(['primaryImage', 'images', 'brand', 'category'])
            ->withCount('variants')
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating');

        // Featured Filter
        if (! empty($filters['featured']) && filter_var($filters['featured'], FILTER_VALIDATE_BOOLEAN)) {
            $query->where('is_featured', true);
        }

        // 1. Category Filter (including children)
        if (! empty($filters['category'])) {
            $cat = is_numeric($filters['category'])
                ? Category::find($filters['category'])
                : Category::where('slug', $filters['category'])->first();

            if ($cat) {
                $categoryIds = Category::where('id', $cat->id)
                    ->orWhere('parent_id', $cat->id)
                    ->pluck('id')
                    ->toArray();

                $query->where(function ($q) use ($categoryIds) {
                    $q->whereIn('category_id', $categoryIds)
                        ->orWhereHas('categories', fn ($c) => $c->whereIn('categories.id', $categoryIds));
                });
            }
        }

        // 2. Price Range
        if (isset($filters['price_min']) && is_numeric($filters['price_min'])) {
            $query->where('base_price', '>=', (float) $filters['price_min']);
        }
        if (isset($filters['price_max']) && is_numeric($filters['price_max'])) {
            $query->where('base_price', '<=', (float) $filters['price_max']);
        }

        // 3. Brand
        if (! empty($filters['brand'])) {
            $brandParam = $filters['brand'];
            $query->whereHas('brand', function ($b) use ($brandParam) {
                $b->where('slug', $brandParam)->orWhere('id', $brandParam);
            });
        }

        // 4. In Stock
        if (! empty($filters['in_stock']) && filter_var($filters['in_stock'], FILTER_VALIDATE_BOOLEAN)) {
            $query->where(function ($q) {
                $q->where('track_stock', false)
                    ->orWhere('stock_qty', '>', 0);
            });
        }

        // 5. On Sale
        if (! empty($filters['on_sale']) && filter_var($filters['on_sale'], FILTER_VALIDATE_BOOLEAN)) {
            $query->whereNotNull('compare_at_price')
                ->whereColumn('compare_at_price', '>', 'base_price');
        }

        // 6. Option Values (Color, Size)
        if (! empty($filters['color'])) {
            $color = (string) $filters['color'];
            $query->whereHas('variants.optionValues', fn ($ov) => $ov->where('value', 'like', "%{$color}%"));
        }
        if (! empty($filters['size'])) {
            $size = (string) $filters['size'];
            $query->whereHas('variants.optionValues', fn ($ov) => $ov->where('value', $size));
        }

        // 7. Search text
        if (! empty($filters['search']) || ! empty($filters['q'])) {
            $searchTerm = trim((string) ($filters['search'] ?? $filters['q']));
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('sku', 'like', "%{$searchTerm}%")
                    ->orWhere('short_description', 'like', "%{$searchTerm}%");
            });
        }

        // 8. Rating Filter
        if (! empty($filters['rating']) && is_numeric($filters['rating'])) {
            $minRating = (float) $filters['rating'];
            $query->whereRaw('(SELECT AVG(rating) FROM reviews WHERE reviews.product_id = products.id AND reviews.is_approved = 1) >= ?', [$minRating]);
        }

        // 9. Sorting
        $sort = $filters['sort'] ?? 'newest';
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('base_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('base_price', 'desc');
                break;
            case 'popular':
                $query->orderBy('is_featured', 'desc')->latest('id');
                break;
            case 'rating':
                $query->orderByDesc('approved_reviews_avg_rating')->latest('id');
                break;
            case 'newest':
            default:
                $query->latest('id');
                break;
        }

        $paginator = $query->paginate($perPage);

        // Transform collection to optimized card payload
        $paginator->getCollection()->transform(fn (Product $product) => self::formatProductCard($product));

        return $paginator;
    }

    /**
     * Get available catalog filter options (brands, categories, colors, sizes, price range).
     *
     * @return array<string, mixed>
     */
    public static function getCatalogFilters(): array
    {
        return Cache::remember('storefront_catalog_filters', 3600, function () {
            $brands = Brand::select('id', 'name', 'slug')
                ->whereHas('products', fn ($q) => $q->whereIn('status', ['active', 'published']))
                ->withCount(['products' => fn ($q) => $q->whereIn('status', ['active', 'published'])])
                ->orderBy('name')
                ->get();

            $categories = Category::select('id', 'name', 'slug', 'parent_id', 'image')
                ->where('is_active', true)
                ->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->where('is_active', true)->select('id', 'name', 'slug', 'parent_id')])
                ->withCount(['products' => fn ($q) => $q->whereIn('status', ['active', 'published'])])
                ->orderBy('sort_order')
                ->get();

            $colors = ProductOptionValue::whereHas('optionType', fn ($q) => $q->where('name', 'like', '%color%'))
                ->whereNotNull('color_hex')
                ->select('value', 'color_hex')
                ->distinct()
                ->get()
                ->map(fn ($c) => ['name' => $c->value, 'hex' => $c->color_hex])
                ->unique('name')
                ->values();

            $sizes = ProductOptionValue::whereHas('optionType', fn ($q) => $q->where('name', 'like', '%size%'))
                ->select('value')
                ->distinct()
                ->pluck('value')
                ->values();

            $priceStats = Product::whereIn('status', ['active', 'published'])
                ->selectRaw('MIN(base_price) as min_price, MAX(base_price) as max_price')
                ->first();

            return [
                'brands' => $brands,
                'categories' => $categories,
                'colors' => $colors,
                'sizes' => $sizes,
                'price_range' => [
                    'min' => (float) floor($priceStats->min_price ?? 0),
                    'max' => (float) ceil($priceStats->max_price ?? 300),
                ],
            ];
        });
    }

    /**
     * Format a product for small card payload.
     *
     * @return array<string, mixed>
     */
    public static function formatProductCard(Product $product): array
    {
        $allImages = $product->images;
        $primaryImg = $product->primaryImage ?: $allImages->first();
        $secondImg = $allImages->count() > 1 ? $allImages->get(1) : null;

        $hasSale = $product->compare_at_price && ($product->compare_at_price > $product->base_price);
        $salePercent = $hasSale
            ? (int) round((($product->compare_at_price - $product->base_price) / $product->compare_at_price) * 100)
            : 0;

        $rating = $product->approved_reviews_avg_rating !== null
            ? (float) $product->approved_reviews_avg_rating
            : ($product->relationLoaded('approvedReviews') ? (float) ($product->approvedReviews->avg('rating') ?: 5.0) : 5.0);
        $reviewsCount = $product->approved_reviews_count ?? ($product->relationLoaded('approvedReviews') ? $product->approvedReviews->count() : 0);
        $hasVariants = isset($product->variants_count)
            ? ($product->variants_count > 0)
            : ($product->relationLoaded('variants') ? $product->variants->isNotEmpty() : false);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'price' => (float) $product->base_price,
            'compare_at_price' => $product->compare_at_price ? (float) $product->compare_at_price : null,
            'is_on_sale' => $hasSale,
            'sale_percentage' => $salePercent,
            'is_featured' => (bool) $product->is_featured,
            'has_variants' => (bool) $hasVariants,
            'rating' => round((float) $rating, 1),
            'reviews_count' => (int) $reviewsCount,
            'in_stock' => ! $product->track_stock || $product->stock_qty > 0,
            'primary_image' => $primaryImg ? [
                'url' => $primaryImg->url,
                'thumb_url' => $primaryImg->thumb_url,
                'medium_url' => $primaryImg->medium_url,
                'alt' => $primaryImg->alt,
            ] : null,
            'second_image' => $secondImg ? [
                'url' => $secondImg->url,
                'thumb_url' => $secondImg->thumb_url,
                'medium_url' => $secondImg->medium_url,
                'alt' => $secondImg->alt,
            ] : null,
            'video_url' => $product->video_url ?: ($product->video_path ? asset('storage/'.$product->video_path) : null),
            'video_poster' => $product->video_poster,
            'brand' => $product->brand ? ['name' => $product->brand->name, 'slug' => $product->brand->slug] : null,
            'category' => $product->category ? ['name' => $product->category->name, 'slug' => $product->category->slug] : null,
        ];
    }

    /**
     * Get full product details for public single product view.
     *
     * @return array<string, mixed>|null
     */
    public static function getProductDetails(string $slug): ?array
    {
        $product = Product::where('slug', $slug)
            ->whereIn('status', ['active', 'published'])
            ->with([
                'category',
                'categories',
                'brand',
                'tags',
                'images',
                'optionTypes.values',
                'variants.optionValues',
                'variants.image',
                'detailsSections',
                'specs',
                'approvedReviews',
            ])
            ->first();

        if (! $product) {
            return null;
        }

        // Compute rating summary
        $reviews = $product->approvedReviews;
        $reviewCount = $reviews->count();
        $averageRating = $reviewCount > 0 ? round($reviews->avg('rating'), 1) : 0.0;

        $ratingBreakdown = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($reviews as $rev) {
            $ratingBreakdown[$rev->rating] = ($ratingBreakdown[$rev->rating] ?? 0) + 1;
        }

        // Hydrate related products
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->whereIn('status', ['active', 'published'])
            ->with(['primaryImage', 'images'])
            ->take(4)
            ->get()
            ->map(fn (Product $p) => self::formatProductCard($p));

        return [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'status' => $product->status,
                'is_featured' => (bool) $product->is_featured,
                'base_price' => (float) $product->base_price,
                'compare_at_price' => $product->compare_at_price ? (float) $product->compare_at_price : null,
                'cost_price' => $product->cost_price ? (float) $product->cost_price : null,
                'track_stock' => (bool) $product->track_stock,
                'stock_qty' => (int) $product->stock_qty,
                'low_stock_threshold' => (int) $product->low_stock_threshold,
                'allow_backorder' => (bool) $product->allow_backorder,
                'weight' => $product->weight,
                'dimensions' => [
                    'length' => $product->length,
                    'width' => $product->width,
                    'height' => $product->height,
                ],
                'video_url' => $product->video_url ?: ($product->video_path ? asset('storage/'.$product->video_path) : null),
                'video_poster' => $product->video_poster,
                'seo' => [
                    'title' => $product->seo_title ?: $product->name,
                    'description' => $product->seo_description ?: $product->short_description,
                    'social_image' => $product->seo_social_image,
                ],
                'brand' => $product->brand ? [
                    'id' => $product->brand->id,
                    'name' => $product->brand->name,
                    'slug' => $product->brand->slug,
                ] : null,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
                'categories' => $product->categories->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                ]),
                'tags' => $product->tags->pluck('name'),
                'images' => $product->images->map(fn ($img) => [
                    'id' => $img->id,
                    'variant_id' => $img->variant_id,
                    'url' => $img->url,
                    'thumb_url' => $img->thumb_url,
                    'medium_url' => $img->medium_url,
                    'large_url' => $img->large_url,
                    'alt' => $img->alt,
                    'is_primary' => (bool) $img->is_primary,
                    'sort_order' => $img->sort_order,
                ]),
                'option_types' => $product->optionTypes->map(fn ($ot) => [
                    'id' => $ot->id,
                    'name' => $ot->name,
                    'values' => $ot->values->map(fn ($val) => [
                        'id' => $val->id,
                        'name' => $val->value,
                        'hex_color' => $val->color_hex,
                    ]),
                ]),
                'variants' => $product->variants->map(fn ($var) => [
                    'id' => $var->id,
                    'sku' => $var->sku,
                    'price' => (float) $var->price,
                    'compare_at_price' => $var->compare_at_price ? (float) $var->compare_at_price : null,
                    'stock_qty' => (int) $var->stock_qty,
                    'image' => $var->image ? $var->image->url : null,
                    'is_active' => (bool) $var->is_active,
                    'option_values' => $var->optionValues->map(fn ($ov) => [
                        'id' => $ov->id,
                        'option_type_id' => $ov->option_type_id,
                        'value' => $ov->value,
                        'hex_color' => $ov->color_hex,
                    ]),
                ]),
                'sections' => $product->detailsSections->map(fn ($sec) => [
                    'id' => $sec->id,
                    'title' => $sec->title,
                    'content' => $sec->content,
                    'is_open_by_default' => (bool) $sec->is_open_by_default,
                    'sort_order' => $sec->sort_order,
                ]),
                'specs' => $product->specs->map(fn ($sp) => [
                    'id' => $sp->id,
                    'label' => $sp->label,
                    'value' => $sp->value,
                    'sort_order' => $sp->sort_order,
                ]),
                'rating_summary' => [
                    'average' => $averageRating,
                    'count' => $reviewCount,
                    'breakdown' => $ratingBreakdown,
                ],
                'reviews' => $reviews->map(fn ($r) => [
                    'id' => $r->id,
                    'author_name' => $r->author_name,
                    'rating' => (int) $r->rating,
                    'title' => $r->title,
                    'body' => $r->body,
                    'review_date' => $r->review_date ? $r->review_date->format('M d, Y') : ($r->created_at ? $r->created_at->format('M d, Y') : null),
                    'helpful_count' => (int) $r->helpful_count,
                ]),
            ],
            'related_products' => $relatedProducts,
        ];
    }

    /**
     * Search suggestions for fast search dropdown (max 6 items, under 50ms).
     *
     * @return array{products: array, categories: array}
     */
    public static function getSearchSuggestions(string $query): array
    {
        $term = trim($query);
        if (empty($term)) {
            return ['products' => [], 'categories' => []];
        }

        $products = Product::whereIn('status', ['active', 'published'])
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%");
            })
            ->with('primaryImage')
            ->take(4)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => (float) $p->base_price,
                'thumbnail' => $p->primaryImage ? $p->primaryImage->thumb_url : null,
            ]);

        $categories = Category::where('is_active', true)
            ->where('name', 'like', "%{$term}%")
            ->take(2)
            ->get()
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'image' => $c->image,
            ]);

        return [
            'products' => $products->toArray(),
            'categories' => $categories->toArray(),
        ];
    }

    /**
     * Sanitize HTML content to prevent XSS.
     */
    public static function sanitizeHtml(?string $html): ?string
    {
        if (empty($html)) {
            return $html;
        }

        // Strip scripts, iframes, and on* attributes
        $cleaned = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $cleaned = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $cleaned);
        $cleaned = preg_replace('/\bon\w+\s*=\s*(["\']).*?\1/i', '', $cleaned);
        $cleaned = preg_replace('/\bjavascript\s*:\s*/i', '', $cleaned);

        return $cleaned;
    }

    /**
     * Save product with all variations, images, sections, specs, and tags.
     */
    public static function saveProduct(array $data, ?Product $product = null): Product
    {
        return DB::transaction(function () use ($data, $product) {
            $isNew = ! $product;
            $product = $product ?: new Product;

            // 1. Basics
            $baseSlug = Str::slug($data['slug'] ?? $data['name']);
            $slug = $baseSlug;
            $counter = 1;
            while (Product::where('slug', $slug)->where('id', '!=', $product->id ?? 0)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            $sku = $data['sku'] ?? ('SKU-'.strtoupper(Str::random(8)));

            $product->fill([
                'category_id' => $data['category_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'name' => $data['name'],
                'slug' => $slug,
                'sku' => $sku,
                'short_description' => $data['short_description'] ?? null,
                'description' => self::sanitizeHtml($data['description'] ?? null),
                'status' => $data['status'] ?? 'active',
                'is_featured' => ! empty($data['is_featured']),
                'base_price' => $data['base_price'] ?? 0.0,
                'compare_at_price' => $data['compare_at_price'] ?? null,
                'cost_price' => $data['cost_price'] ?? null,
                'track_stock' => array_key_exists('track_stock', $data) ? (bool) $data['track_stock'] : true,
                'stock_qty' => $data['stock_qty'] ?? 0,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
                'allow_backorder' => ! empty($data['allow_backorder']),
                'weight' => $data['weight'] ?? null,
                'length' => $data['length'] ?? null,
                'width' => $data['width'] ?? null,
                'height' => $data['height'] ?? null,
                'tax_class' => $data['tax_class'] ?? 'standard',
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'seo_social_image' => $data['seo_social_image'] ?? null,
                'video_url' => $data['video_url'] ?? null,
                'video_path' => $data['video_path'] ?? null,
                'video_poster' => $data['video_poster'] ?? null,
                'related_products_json' => $data['related_products_json'] ?? null,
                'frequently_bought_together_json' => $data['frequently_bought_together_json'] ?? null,
            ]);

            $product->save();

            // 2. Extra Categories & Tags
            if (isset($data['category_ids']) && is_array($data['category_ids'])) {
                $product->categories()->sync($data['category_ids']);
            }
            if (isset($data['tags']) && is_array($data['tags'])) {
                $tagIds = [];
                foreach ($data['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate(['name' => trim($tagName)], ['slug' => Str::slug($tagName)]);
                    $tagIds[] = $tag->id;
                }
                $product->tags()->sync($tagIds);
            }

            // 3. Option Types & Values
            if (isset($data['options']) && is_array($data['options'])) {
                $existingTypes = $product->optionTypes()->pluck('id')->toArray();
                $processedTypeIds = [];

                foreach ($data['options'] as $typeIndex => $optTypeData) {
                    $optType = $product->optionTypes()->updateOrCreate(
                        ['name' => $optTypeData['name']],
                        ['sort_order' => $typeIndex + 1]
                    );
                    $processedTypeIds[] = $optType->id;

                    if (isset($optTypeData['values']) && is_array($optTypeData['values'])) {
                        foreach ($optTypeData['values'] as $valData) {
                            $valName = is_array($valData) ? $valData['name'] : $valData;
                            $hex = is_array($valData) ? ($valData['hex_color'] ?? null) : null;
                            $optType->values()->updateOrCreate(
                                ['value' => $valName],
                                ['color_hex' => $hex]
                            );
                        }
                    }
                }
            }

            // 4. Variants
            if (isset($data['variants']) && is_array($data['variants'])) {
                foreach ($data['variants'] as $varData) {
                    $varSku = $varData['sku'] ?? ($product->sku.'-'.Str::random(4));
                    $variant = $product->variants()->updateOrCreate(
                        ['sku' => $varSku],
                        [
                            'price' => $varData['price'] ?? $product->base_price,
                            'compare_at_price' => $varData['compare_at_price'] ?? null,
                            'stock_qty' => $varData['stock_qty'] ?? 0,
                            'is_active' => array_key_exists('is_active', $varData) ? (bool) $varData['is_active'] : true,
                            'option_values_json' => $varData['option_values_json'] ?? null,
                        ]
                    );

                    // Sync option values to pivot if IDs provided
                    if (! empty($varData['option_value_ids'])) {
                        $variant->optionValues()->sync($varData['option_value_ids']);
                    }
                }
            }

            // 5. Images
            if (isset($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $imgIndex => $imgData) {
                    ProductImage::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'path' => $imgData['path'] ?? null,
                            'external_url' => $imgData['url'] ?? ($imgData['external_url'] ?? null),
                        ],
                        [
                            'thumb_path' => $imgData['thumb_path'] ?? null,
                            'medium_path' => $imgData['medium_path'] ?? null,
                            'large_path' => $imgData['large_path'] ?? null,
                            'alt' => $imgData['alt'] ?? $product->name,
                            'sort_order' => $imgIndex + 1,
                            'is_primary' => ! empty($imgData['is_primary']) || ($imgIndex === 0),
                            'variant_id' => $imgData['variant_id'] ?? null,
                        ]
                    );
                }
            }

            // 6. Details Sections (Accordion blocks)
            if (isset($data['sections']) && is_array($data['sections'])) {
                $product->detailsSections()->delete();
                foreach ($data['sections'] as $secIndex => $secData) {
                    ProductDetailsSection::create([
                        'product_id' => $product->id,
                        'title' => $secData['title'],
                        'content' => self::sanitizeHtml($secData['content']),
                        'is_open_by_default' => ! empty($secData['is_open_by_default']),
                        'sort_order' => $secIndex + 1,
                    ]);
                }
            }

            // 7. Specifications
            if (isset($data['specs']) && is_array($data['specs'])) {
                $product->specs()->delete();
                foreach ($data['specs'] as $spIndex => $spData) {
                    ProductSpec::create([
                        'product_id' => $product->id,
                        'label' => $spData['label'] ?? ($spData['name'] ?? ''),
                        'value' => $spData['value'] ?? '',
                        'sort_order' => $spIndex + 1,
                    ]);
                }
            }

            return $product;
        });
    }

    /**
     * Duplicate a product including variants, options, sections, specs, and images.
     */
    public static function duplicate(Product $source): Product
    {
        return DB::transaction(function () use ($source) {
            $clone = $source->replicate([
                'slug',
                'sku',
            ]);

            $clone->name = "{$source->name} (Copy)";
            $clone->slug = Str::slug($clone->name).'-'.uniqid();
            $clone->sku = "{$source->sku}-COPY-".strtoupper(Str::random(4));
            $clone->status = 'draft';
            $clone->save();

            // Duplicate images
            foreach ($source->images as $img) {
                $cloneImg = $img->replicate(['product_id', 'variant_id']);
                $cloneImg->product_id = $clone->id;
                $cloneImg->save();
            }

            // Duplicate option types and values
            $valMapping = [];
            foreach ($source->optionTypes as $ot) {
                $cloneOt = $ot->replicate(['product_id']);
                $cloneOt->product_id = $clone->id;
                $cloneOt->save();

                foreach ($ot->values as $val) {
                    $cloneVal = $val->replicate(['option_type_id']);
                    $cloneVal->option_type_id = $cloneOt->id;
                    $cloneVal->save();
                    $valMapping[$val->id] = $cloneVal->id;
                }
            }

            // Duplicate variants
            foreach ($source->variants as $var) {
                $cloneVar = $var->replicate(['product_id', 'sku']);
                $cloneVar->product_id = $clone->id;
                $cloneVar->sku = "{$var->sku}-COPY-".strtoupper(Str::random(4));
                $cloneVar->save();

                $newValIds = [];
                foreach ($var->optionValues as $origVal) {
                    if (isset($valMapping[$origVal->id])) {
                        $newValIds[] = $valMapping[$origVal->id];
                    }
                }
                $cloneVar->optionValues()->sync($newValIds);
            }

            // Duplicate details sections
            foreach ($source->detailsSections as $sec) {
                $cloneSec = $sec->replicate(['product_id']);
                $cloneSec->product_id = $clone->id;
                $cloneSec->save();
            }

            // Duplicate specs
            foreach ($source->specs as $sp) {
                $cloneSp = $sp->replicate(['product_id']);
                $cloneSp->product_id = $clone->id;
                $cloneSp->save();
            }

            // Duplicate categories & tags
            $clone->categories()->sync($source->categories->pluck('id'));
            $clone->tags()->sync($source->tags->pluck('id'));

            return $clone;
        });
    }

    /**
     * Bulk edit variants tool.
     */
    public static function bulkEditVariants(Product $product, string $action, mixed $value): void
    {
        switch ($action) {
            case 'set_price':
                $product->variants()->update(['price' => (float) $value]);
                break;
            case 'increase_price_percent':
                $pct = 1 + ((float) $value / 100);
                foreach ($product->variants as $variant) {
                    $variant->price = round($variant->price * $pct, 2);
                    $variant->save();
                }
                break;
            case 'set_stock':
                $product->variants()->update(['stock_qty' => (int) $value]);
                break;
        }
    }
}

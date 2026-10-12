<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    public const TREE_CACHE_KEY = 'categories_public_tree';

    /**
     * Get full public category tree with active children and products count.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getTree(): array
    {
        return Cache::rememberForever(self::TREE_CACHE_KEY, function () {
            $rootCategories = Category::whereNull('parent_id')
                ->where('is_active', true)
                ->with(['children' => function ($query) {
                    $query->where('is_active', true)
                        ->with(['children' => function ($sub) {
                            $sub->where('is_active', true)->withCount('products');
                        }])
                        ->withCount('products')
                        ->orderBy('sort_order');
                }])
                ->withCount('products')
                ->orderBy('sort_order')
                ->get();

            return $rootCategories->map(fn (Category $cat) => self::formatCategoryNode($cat))->toArray();
        });
    }

    /**
     * Get single category by slug with breadcrumbs, active direct children, and product count.
     */
    public static function getBySlug(string $slug): ?array
    {
        $cacheKey = "category_public_{$slug}";

        return Cache::rememberForever($cacheKey, function () use ($slug) {
            $category = Category::where('slug', $slug)
                ->where('is_active', true)
                ->with(['children' => function ($query) {
                    $query->where('is_active', true)
                        ->withCount('products')
                        ->orderBy('sort_order');
                }])
                ->withCount('products')
                ->first();

            if (! $category) {
                return;
            }

            // Generate breadcrumbs path from root down to this category
            $breadcrumbs = [];
            $curr = $category;
            while ($curr) {
                array_unshift($breadcrumbs, [
                    'id' => $curr->id,
                    'name' => $curr->name,
                    'slug' => $curr->slug,
                ]);
                $curr = $curr->parent;
            }

            return [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'image' => $category->image,
                    'icon' => $category->icon,
                    'sort_order' => $category->sort_order,
                    'is_featured' => $category->is_featured,
                    'seo_title' => $category->seo_title,
                    'seo_description' => $category->seo_description,
                    'products_count' => $category->products_count,
                ],
                'breadcrumbs' => $breadcrumbs,
                'children' => $category->children->map(fn (Category $child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'description' => $child->description,
                    'image' => $child->image,
                    'sort_order' => $child->sort_order,
                    'is_featured' => $child->is_featured,
                    'products_count' => $child->products_count,
                ])->toArray(),
            ];
        });
    }

    /**
     * Clear all cached category data.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::TREE_CACHE_KEY);

        // Clear individual category slug caches
        $slugs = Category::pluck('slug');
        foreach ($slugs as $slug) {
            Cache::forget("category_public_{$slug}");
        }
    }

    /**
     * Format a category node recursively.
     *
     * @return array<string, mixed>
     */
    protected static function formatCategoryNode(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'image' => $category->image,
            'icon' => $category->icon,
            'sort_order' => $category->sort_order,
            'is_featured' => $category->is_featured,
            'products_count' => $category->products_count,
            'children' => $category->children->map(fn (Category $c) => self::formatCategoryNode($c))->values()->toArray(),
        ];
    }
}

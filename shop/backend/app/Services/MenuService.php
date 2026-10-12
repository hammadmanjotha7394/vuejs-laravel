<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MenuService
{
    /**
     * Get nested public menu structure by slug (cached).
     *
     * @return array<string, mixed>|null
     */
    public static function getPublicMenu(string $slug): ?array
    {
        $cacheKey = "menu_public_{$slug}";

        return Cache::rememberForever($cacheKey, function () use ($slug) {
            $menu = Menu::where('slug', $slug)
                ->where('is_active', true)
                ->with(['allItems' => function ($query) {
                    $query->where('is_active', true)->orderBy('sort_order');
                }])
                ->first();

            if (! $menu) {
                return;
            }

            // Group items into nested tree
            $allItems = $menu->allItems;
            $rootItems = $allItems->whereNull('parent_id')->sortBy('sort_order');

            $structuredItems = [];
            foreach ($rootItems as $root) {
                $structuredItems[] = self::formatMenuItem($root, $allItems);
            }

            return [
                'id' => $menu->id,
                'name' => $menu->name,
                'slug' => $menu->slug,
                'items' => $structuredItems,
            ];
        });
    }

    /**
     * Clear all cached public menus.
     */
    public static function clearCache(): void
    {
        $slugs = Menu::pluck('slug');
        foreach ($slugs as $slug) {
            Cache::forget("menu_public_{$slug}");
        }
        Cache::forget('menu_public_main');
        Cache::forget('menu_public_footer');
    }

    /**
     * Format a menu item and construct mega menu columns if applicable.
     *
     * @param  Collection<int, MenuItem>  $allItems
     * @return array<string, mixed>
     */
    protected static function formatMenuItem(MenuItem $item, $allItems): array
    {
        $children = $allItems->where('parent_id', $item->id)->sortBy('sort_order');

        $hasColumns = $children->where('column_index', '>', 0)->isNotEmpty()
            || $children->where('type', 'mega_group')->isNotEmpty();
        $hasFeaturedCard = ! empty($item->image);

        $isMega = $hasColumns || $hasFeaturedCard;

        $columns = [];
        if ($isMega) {
            // Group children into 1-5 columns
            for ($col = 1; $col <= 5; $col++) {
                $colChildren = $children->where('column_index', $col);
                if ($colChildren->isEmpty()) {
                    continue;
                }

                // If first item in column is a mega_group, use its label as column title
                $firstItem = $colChildren->first();
                $columnTitle = null;
                $columnItems = [];

                if ($firstItem && $firstItem->type === 'mega_group') {
                    $columnTitle = $firstItem->label;
                    // Sub-items of this mega group
                    $groupChildren = $allItems->where('parent_id', $firstItem->id)->sortBy('sort_order');
                    foreach ($groupChildren as $gChild) {
                        $columnItems[] = self::formatSimpleItem($gChild);
                    }
                } else {
                    foreach ($colChildren as $cChild) {
                        $columnItems[] = self::formatSimpleItem($cChild);
                    }
                }

                $columns[] = [
                    'column_index' => $col,
                    'title' => $columnTitle,
                    'items' => $columnItems,
                ];
            }
        }

        return [
            'id' => $item->id,
            'label' => $item->label,
            'type' => $item->type,
            'target_id' => $item->target_id,
            'url' => $item->url,
            'open_in_new_tab' => (bool) $item->open_in_new_tab,
            'badge_text' => $item->badge_text,
            'sort_order' => $item->sort_order,
            'column_index' => $item->column_index,
            'is_mega' => $isMega,
            'featured_card' => $hasFeaturedCard ? [
                'image' => $item->image,
                'title' => $item->featured_title,
                'button_text' => $item->featured_button_text,
                'button_url' => $item->featured_button_url,
            ] : null,
            'columns' => $columns,
            'children' => $children->map(fn (MenuItem $c) => self::formatSimpleItem($c))->values()->toArray(),
        ];
    }

    /**
     * Format a simple leaf or child menu item.
     *
     * @return array<string, mixed>
     */
    protected static function formatSimpleItem(MenuItem $item): array
    {
        return [
            'id' => $item->id,
            'label' => $item->label,
            'type' => $item->type,
            'target_id' => $item->target_id,
            'url' => $item->url,
            'open_in_new_tab' => (bool) $item->open_in_new_tab,
            'badge_text' => $item->badge_text,
            'sort_order' => $item->sort_order,
            'column_index' => $item->column_index,
            'image' => $item->image,
        ];
    }
}

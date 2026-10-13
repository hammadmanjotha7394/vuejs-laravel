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

        $hasSubChildren = $children->contains(function (MenuItem $c) use ($allItems) {
            return $c->type === 'mega_group' || $allItems->where('parent_id', $c->id)->isNotEmpty();
        });
        $hasColumns = $children->where('column_index', '>', 0)->isNotEmpty() || $hasSubChildren;
        $hasFeaturedCard = ! empty($item->image) || ! empty($item->featured_title);

        $isMega = $hasColumns || $hasFeaturedCard || $children->isNotEmpty();

        $columns = [];
        if ($isMega && $children->isNotEmpty()) {
            $directLinksByCol = [];

            foreach ($children->sortBy([['column_index', 'asc'], ['sort_order', 'asc']]) as $child) {
                $subItems = $allItems->where('parent_id', $child->id)->sortBy('sort_order');

                if ($child->type === 'mega_group' || $subItems->isNotEmpty()) {
                    $formattedSubs = [];
                    foreach ($subItems as $gChild) {
                        $formattedSubs[] = self::formatSimpleItem($gChild);
                    }
                    if (empty($formattedSubs) && ! empty($child->url)) {
                        $formattedSubs[] = self::formatSimpleItem($child);
                    }

                    $colNum = $child->column_index > 0 ? $child->column_index : (count($columns) + 1);
                    $columns[] = [
                        'column_index' => (int) $colNum,
                        'title' => $child->label,
                        'heading' => $child->label,
                        'url' => $child->url,
                        'badge_text' => $child->badge_text,
                        'items' => $formattedSubs,
                        'links' => $formattedSubs,
                    ];
                } else {
                    $colKey = $child->column_index > 0 ? $child->column_index : 1;
                    $directLinksByCol[$colKey][] = self::formatSimpleItem($child);
                }
            }

            foreach ($directLinksByCol as $colNum => $links) {
                $columns[] = [
                    'column_index' => (int) $colNum,
                    'title' => null,
                    'heading' => null,
                    'items' => $links,
                    'links' => $links,
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
                'title' => $item->featured_title ?: $item->label,
                'badge' => $item->badge_text,
                'button_text' => $item->featured_button_text ?: 'Discover Now',
                'button_url' => $item->featured_button_url ?: ($item->url ?: '/shop'),
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

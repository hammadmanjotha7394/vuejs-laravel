<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\MenuService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    /**
     * List all menus with total items count.
     */
    public function index(): JsonResponse
    {
        $menus = Menu::withCount('allItems')->get();

        return response()->json([
            'data' => $menus,
        ]);
    }

    /**
     * Show a menu with all its items.
     */
    public function show(Menu $menu): JsonResponse
    {
        $menu->load(['allItems' => function ($query) {
            $query->orderBy('sort_order');
        }]);

        return response()->json([
            'data' => $menu,
        ]);
    }

    /**
     * Create a new menu.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:menus,slug',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $menu = Menu::create($validated);
        MenuService::clearCache();

        return response()->json([
            'message' => 'Menu created successfully.',
            'data' => $menu,
        ], 201);
    }

    /**
     * Update menu meta.
     */
    public function update(Request $request, Menu $menu): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'slug' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('menus', 'slug')->ignore($menu->id)],
            'is_active' => 'nullable|boolean',
        ]);

        $menu->update($validated);
        MenuService::clearCache();

        return response()->json([
            'message' => 'Menu updated successfully.',
            'data' => $menu,
        ]);
    }

    /**
     * Delete a menu and its associated items.
     */
    public function destroy(Menu $menu): JsonResponse
    {
        $menu->delete();
        MenuService::clearCache();

        return response()->json([
            'message' => 'Menu deleted successfully.',
        ]);
    }

    /**
     * Add a menu item to a menu.
     */
    public function storeItem(Request $request, Menu $menu): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'type' => 'required|in:category,page,custom_url,mega_group',
            'parent_id' => [
                'nullable',
                Rule::exists('menu_items', 'id')->where('menu_id', $menu->id),
            ],
            'target_id' => 'nullable|integer',
            'url' => 'nullable|string|max:255',
            'open_in_new_tab' => 'nullable|boolean',
            'image' => 'nullable|string|max:500',
            'featured_title' => 'nullable|string|max:150',
            'featured_button_text' => 'nullable|string|max:100',
            'featured_button_url' => 'nullable|string|max:255',
            'badge_text' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'column_index' => 'nullable|integer|min:0|max:5',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['menu_id'] = $menu->id;
        $item = MenuItem::create($validated);
        MenuService::clearCache();

        return response()->json([
            'message' => 'Menu item created successfully.',
            'data' => $item,
        ], 201);
    }

    /**
     * Update an individual menu item.
     */
    public function updateItem(Request $request, MenuItem $item): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'sometimes|required|string|max:100',
            'type' => 'sometimes|required|in:category,page,custom_url,mega_group',
            'parent_id' => [
                'nullable',
                Rule::exists('menu_items', 'id')->where('menu_id', $item->menu_id),
                function ($attribute, $value, $fail) use ($item) {
                    if ((int) $value === (int) $item->id) {
                        $fail('A menu item cannot be its own parent.');
                    }
                },
            ],
            'target_id' => 'nullable|integer',
            'url' => 'nullable|string|max:255',
            'open_in_new_tab' => 'nullable|boolean',
            'image' => 'nullable|string|max:500',
            'featured_title' => 'nullable|string|max:150',
            'featured_button_text' => 'nullable|string|max:100',
            'featured_button_url' => 'nullable|string|max:255',
            'badge_text' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'column_index' => 'nullable|integer|min:0|max:5',
            'is_active' => 'nullable|boolean',
        ]);

        $item->update($validated);
        MenuService::clearCache();

        return response()->json([
            'message' => 'Menu item updated successfully.',
            'data' => $item,
        ]);
    }

    /**
     * Delete an individual menu item.
     */
    public function destroyItem(MenuItem $item): JsonResponse
    {
        $item->delete();
        MenuService::clearCache();

        return response()->json([
            'message' => 'Menu item removed successfully.',
        ]);
    }

    /**
     * Reorder menu items (drag-and-drop).
     */
    public function reorderItems(Request $request, Menu $menu): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:menu_items,id',
            'items.*.sort_order' => 'required|integer|min:0',
            'items.*.parent_id' => 'nullable|exists:menu_items,id',
            'items.*.column_index' => 'nullable|integer|min:0|max:5',
        ]);

        foreach ($request->input('items') as $item) {
            $update = ['sort_order' => $item['sort_order']];
            if (array_key_exists('parent_id', $item)) {
                $update['parent_id'] = $item['parent_id'];
            }
            if (array_key_exists('column_index', $item)) {
                $update['column_index'] = $item['column_index'];
            }
            MenuItem::where('id', $item['id'])
                ->where('menu_id', $menu->id)
                ->update($update);
        }

        MenuService::clearCache();

        return response()->json([
            'message' => 'Menu items reordered successfully.',
        ]);
    }

    /**
     * Upload an image for a menu featured card or icon.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,webp,svg|max:2048',
        ]);

        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension());

        if ($ext === 'svg') {
            $content = file_get_contents($file->getRealPath());
            if (! SettingService::sanitizeSvg($content)) {
                return response()->json([
                    'message' => 'The uploaded SVG contains unsafe or disallowed scripts.',
                ], 422);
            }
        }

        $filename = 'menu_'.uniqid().'.'.$ext;
        $path = $file->storeAs('menus', $filename, 'public');

        return response()->json([
            'url' => asset('storage/'.$path),
            'path' => $path,
        ]);
    }
}

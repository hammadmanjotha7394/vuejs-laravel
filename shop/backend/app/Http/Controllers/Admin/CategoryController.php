<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategoryService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * List all categories with parent relationship and products count.
     */
    public function index(): JsonResponse
    {
        $categories = Category::with('parent')
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Create a new category with automated slug and hierarchy validation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:120|unique:categories,slug',
            'description' => 'nullable|string|max:1000',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'seo_title' => 'nullable|string|max:150',
            'seo_description' => 'nullable|string|max:500',
        ]);

        $baseSlug = Str::slug($validated['slug'] ?? $validated['name']);
        $slug = $baseSlug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }
        $validated['slug'] = $slug;

        $category = Category::create($validated);
        CategoryService::clearCache();

        return response()->json([
            'message' => 'Category created successfully.',
            'data' => $category->load('parent')->loadCount('products'),
        ], 201);
    }

    /**
     * Show single category.
     */
    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'data' => $category->load('parent', 'children')->loadCount('products'),
        ]);
    }

    /**
     * Update an existing category.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'slug' => ['sometimes', 'nullable', 'string', 'max:120', Rule::unique('categories', 'slug')->ignore($category->id)],
            'description' => 'nullable|string|max:1000',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($category) {
                    if ((int) $value === (int) $category->id) {
                        $fail('A category cannot be its own parent.');
                    }
                },
            ],
            'image' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'seo_title' => 'nullable|string|max:150',
            'seo_description' => 'nullable|string|max:500',
        ]);

        if (array_key_exists('slug', $validated) && empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name'] ?? $category->name);
        }

        $category->update($validated);
        CategoryService::clearCache();

        return response()->json([
            'message' => 'Category updated successfully.',
            'data' => $category->fresh()->load('parent')->loadCount('products'),
        ]);
    }

    /**
     * Delete a category, reparenting child categories to root to prevent orphan data.
     */
    public function destroy(Category $category): JsonResponse
    {
        // Re-parent direct children to avoid orphaned records
        Category::where('parent_id', $category->id)->update(['parent_id' => null]);

        $category->delete();
        CategoryService::clearCache();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }

    /**
     * Reorder categories via drag-and-drop.
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:categories,id',
            'items.*.sort_order' => 'required|integer|min:0',
            'items.*.parent_id' => 'nullable|exists:categories,id',
        ]);

        foreach ($request->input('items') as $item) {
            $update = ['sort_order' => $item['sort_order']];
            if (array_key_exists('parent_id', $item)) {
                $update['parent_id'] = $item['parent_id'];
            }
            Category::where('id', $item['id'])->update($update);
        }

        CategoryService::clearCache();

        return response()->json([
            'message' => 'Categories reordered successfully.',
        ]);
    }

    /**
     * Upload category thumbnail / tile image.
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

        $filename = 'cat_'.uniqid().'.'.$ext;
        $path = $file->storeAs('categories', $filename, 'public');

        return response()->json([
            'url' => asset('storage/'.$path),
            'path' => $path,
        ]);
    }
}

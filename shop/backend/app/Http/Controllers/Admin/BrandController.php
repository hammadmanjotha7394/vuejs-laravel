<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Brand::withCount('products');

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            });
        }

        return response()->json([
            'data' => $query->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => 'nullable|string|max:140|unique:brands,slug',
            'logo' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        if (Brand::where('slug', $slug)->exists()) {
            $slug .= '-'.Str::lower(Str::random(4));
        }

        $brand = Brand::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'logo' => $validated['logo'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'brand.created',
            'subject_type' => Brand::class,
            'subject_id' => $brand->id,
            'properties_json' => ['name' => $brand->name, 'slug' => $brand->slug],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Brand created successfully.',
            'data' => $brand->loadCount('products'),
        ], 201);
    }

    public function update(Request $request, Brand $brand): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:120',
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('brands', 'slug')->ignore($brand->id)],
            'logo' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if (isset($validated['slug']) && $validated['slug'] !== '') {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        $brand->update($validated);

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'brand.updated',
            'subject_type' => Brand::class,
            'subject_id' => $brand->id,
            'properties_json' => ['name' => $brand->name, 'slug' => $brand->slug],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Brand updated successfully.',
            'data' => $brand->fresh()->loadCount('products'),
        ]);
    }

    public function destroy(Request $request, Brand $brand): JsonResponse
    {
        $name = $brand->name;
        $id = $brand->id;
        $brand->delete();

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'brand.deleted',
            'subject_type' => Brand::class,
            'subject_id' => $id,
            'properties_json' => ['name' => $name],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Brand deleted successfully.',
        ]);
    }
}

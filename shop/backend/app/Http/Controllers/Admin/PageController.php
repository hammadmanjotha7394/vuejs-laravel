<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Page;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PageController extends Controller
{
    /**
     * List all CMS pages.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Page::query();

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            });
        }

        $pages = $query->orderBy('title')->get();

        return response()->json([
            'data' => $pages,
        ]);
    }

    /**
     * Create a new CMS page with sanitized HTML and SEO metadata.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'slug' => 'nullable|string|max:200|unique:pages,slug',
            'content' => 'nullable|string',
            'content_html' => 'nullable|string',
            'is_published' => 'nullable|boolean',
            'seo_title' => 'nullable|string|max:200',
            'seo_description' => 'nullable|string|max:500',
        ]);

        $rawContent = $validated['content'] ?? $validated['content_html'] ?? '';

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['title']);

        if (Page::where('slug', $slug)->exists()) {
            $slug .= '-'.Str::lower(Str::random(4));
        }

        $page = Page::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'content' => ProductService::sanitizeHtml($rawContent),
            'is_published' => array_key_exists('is_published', $validated) ? (bool) $validated['is_published'] : true,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
        ]);

        Cache::forget('public_cms_pages_list');
        Cache::forget("public_cms_page_{$page->slug}");

        ActivityLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => 'page.created',
            'subject_type' => Page::class,
            'subject_id' => $page->id,
            'properties_json' => ['title' => $page->title, 'slug' => $page->slug],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Page created successfully.',
            'data' => $page,
        ], 201);
    }

    /**
     * Show a single CMS page.
     */
    public function show(Page $page): JsonResponse
    {
        return response()->json([
            'data' => $page,
        ]);
    }

    /**
     * Update an existing CMS page and bust storefront cache.
     */
    public function update(Request $request, Page $page): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:200',
            'slug' => 'nullable|string|max:200|unique:pages,slug,'.$page->id,
            'content' => 'nullable|string',
            'content_html' => 'nullable|string',
            'is_published' => 'nullable|boolean',
            'seo_title' => 'nullable|string|max:200',
            'seo_description' => 'nullable|string|max:500',
        ]);

        $oldSlug = $page->slug;

        if (! empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }
        if (array_key_exists('content', $validated) || array_key_exists('content_html', $validated)) {
            $raw = $validated['content'] ?? $validated['content_html'] ?? '';
            $validated['content'] = ProductService::sanitizeHtml($raw);
            unset($validated['content_html']);
        }

        $page->update($validated);

        Cache::forget('public_cms_pages_list');
        Cache::forget("public_cms_page_{$oldSlug}");
        Cache::forget("public_cms_page_{$page->slug}");

        return response()->json([
            'message' => 'Page updated successfully.',
            'data' => $page->fresh(),
        ]);
    }

    /**
     * Delete a CMS page.
     */
    public function destroy(Page $page): JsonResponse
    {
        $slug = $page->slug;
        $page->delete();

        Cache::forget('public_cms_pages_list');
        Cache::forget("public_cms_page_{$slug}");

        return response()->json([
            'message' => 'Page deleted.',
        ]);
    }
}

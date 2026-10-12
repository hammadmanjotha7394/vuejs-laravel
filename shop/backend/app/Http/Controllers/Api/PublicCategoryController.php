<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

class PublicCategoryController extends Controller
{
    /**
     * Retrieve full category tree for public storefront (cached).
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => CategoryService::getTree(),
        ]);
    }

    /**
     * Retrieve single category with breadcrumbs, sub-categories, and product count.
     */
    public function show(string $slug): JsonResponse
    {
        $categoryData = CategoryService::getBySlug($slug);

        if (! $categoryData) {
            return response()->json([
                'message' => "Category '{$slug}' not found.",
            ], 404);
        }

        return response()->json([
            'data' => $categoryData,
        ]);
    }
}

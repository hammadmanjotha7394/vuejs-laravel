<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicProductController extends Controller
{
    /**
     * Filter and paginate products for storefront listing.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 20);
        $products = ProductService::filterProducts($request->all(), $perPage);

        return response()->json($products);
    }

    /**
     * Retrieve single product with full specifications, sections, images, and variants.
     */
    public function show(string $slug): JsonResponse
    {
        $details = ProductService::getProductDetails($slug);

        if (! $details) {
            return response()->json([
                'message' => "Product '{$slug}' not found.",
            ], 404);
        }

        return response()->json([
            'data' => $details,
        ]);
    }

    /**
     * Fast search suggestion endpoint for live search overlay (max 6 items).
     */
    public function suggest(Request $request): JsonResponse
    {
        $query = (string) $request->input('q', '');
        $suggestions = ProductService::getSearchSuggestions($query);

        return response()->json([
            'data' => $suggestions,
        ]);
    }

    /**
     * Available catalog facets/filters for sidebar.
     */
    public function filters(): JsonResponse
    {
        $filters = ProductService::getCatalogFilters();

        return response()->json([
            'data' => $filters,
        ]);
    }
}

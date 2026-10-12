<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MenuService;
use Illuminate\Http\JsonResponse;

class PublicMenuController extends Controller
{
    /**
     * Retrieve structured public menu by slug (cached).
     */
    public function show(string $slug): JsonResponse
    {
        $menuData = MenuService::getPublicMenu($slug);

        if (! $menuData) {
            return response()->json([
                'message' => "Menu '{$slug}' not found.",
            ], 404);
        }

        return response()->json([
            'data' => $menuData,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;

class PublicSettingController extends Controller
{
    /**
     * Retrieve public storefront settings (cached, safe values only).
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => SettingService::getPublicSettings(),
        ]);
    }
}

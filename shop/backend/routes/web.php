<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any?}', function () {
    $spaIndex = public_path('app/index.html');
    if (! file_exists($spaIndex)) {
        $spaIndex = public_path('index.html');
    }
    if (file_exists($spaIndex)) {
        return response()->file($spaIndex);
    }

    return response()->json([
        'message' => 'E-commerce API is running. Build frontend or start Vite dev server.',
        'health' => url('/api/health'),
    ]);
})->where('any', '^(?!api|sanctum).*$');

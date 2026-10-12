<?php

use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PublicSettingController;
use Illuminate\Support\Facades\Route;

// Public Health Check
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Public Storefront Settings (safe values only)
Route::get('/public-settings', [PublicSettingController::class, 'index']);

// Guest Auth Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

// Authenticated User Routes (Customers & Staff)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'changePassword']);

    // Admin Routes
    Route::prefix('admin')->group(function () {
        // Staff & User Management
        Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view');
        Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create');
        Route::get('/users/{user}', [UserController::class, 'show'])->middleware('permission:users.view');
        Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.update');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('permission:users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.update');

        // Roles & Permissions Editor
        Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.manage');
        Route::get('/permissions', [RoleController::class, 'permissions'])->middleware('permission:roles.manage');
        Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.manage');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.manage');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.manage');

        // Finance Reports & Metrics
        Route::get('/finance/summary', [FinanceController::class, 'summary'])->middleware('permission:finance.view');

        // Settings System
        Route::get('/settings', [SettingController::class, 'index'])->middleware('permission:settings.view');
        Route::put('/settings', [SettingController::class, 'update'])->middleware('permission:settings.update');
        Route::post('/settings/upload-asset', [SettingController::class, 'uploadAsset'])->middleware('permission:settings.update');

        // Stripe Settings & Connection Testing
        Route::put('/settings/stripe', [SettingController::class, 'updateStripe'])->middleware('permission:stripe.manage');
        Route::post('/settings/stripe/test', [SettingController::class, 'testStripe'])->middleware('permission:stripe.manage');

        // Mail (SMTP) Settings & Testing
        Route::put('/settings/mail', [SettingController::class, 'updateMail'])->middleware('permission:mail.manage');
        Route::post('/settings/mail/test', [SettingController::class, 'testMail'])->middleware('permission:mail.manage');
    });
});

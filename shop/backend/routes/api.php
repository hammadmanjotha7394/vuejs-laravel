<?php

use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Public Health Check
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

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
    });
});

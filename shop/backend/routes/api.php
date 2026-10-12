<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\PublicCategoryController;
use App\Http\Controllers\Api\PublicMarketingController;
use App\Http\Controllers\Api\PublicMenuController;
use App\Http\Controllers\Api\PublicProductController;
use App\Http\Controllers\Api\PublicSettingController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

// Public Health Check
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Public Storefront Settings (safe values only)
Route::get('/public-settings', [PublicSettingController::class, 'index']);

// Public Categories (Cached tree & single lookup)
Route::get('/categories', [PublicCategoryController::class, 'index']);
Route::get('/categories/{slug}', [PublicCategoryController::class, 'show']);

// Public Menus & Mega Menu (Cached)
Route::get('/menu/main', [PublicMenuController::class, 'show'])->defaults('slug', 'main');
Route::get('/menu/footer', [PublicMenuController::class, 'show'])->defaults('slug', 'footer');
Route::get('/menu/{slug}', [PublicMenuController::class, 'show']);

// Public Products Catalog, Details, and Suggestions
Route::get('/products', [PublicProductController::class, 'index']);
Route::get('/products/{slug}', [PublicProductController::class, 'show']);
Route::get('/search/suggest', [PublicProductController::class, 'suggest']);
Route::get('/catalog/filters', [PublicProductController::class, 'filters']);
Route::post('/products/{id}/reviews', [PublicProductController::class, 'storeReview']);
Route::post('/reviews/{id}/helpful', [PublicProductController::class, 'voteHelpful']);

// Public Marketing & Newsletter
Route::get('/hero-slides', [PublicMarketingController::class, 'heroSlides']);
Route::get('/popups', [PublicMarketingController::class, 'popups']);
Route::get('/reviews/featured', [PublicMarketingController::class, 'featuredReviews']);
Route::post('/newsletter/subscribe', [PublicMarketingController::class, 'subscribe']);

// Storefront Cart Operations (Server is source of truth for price & inventory)
Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart/items', [CartController::class, 'store']);
Route::put('/cart/items/{id}', [CartController::class, 'update']);
Route::delete('/cart/items/{id}', [CartController::class, 'destroy']);
Route::delete('/cart', [CartController::class, 'clear']);
Route::post('/cart/coupon', [CartController::class, 'applyCoupon']);
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon']);
Route::post('/cart/merge', [CartController::class, 'merge']);

// Storefront Wishlist Operations (Guest sync & Authenticated tracking)
Route::get('/wishlist', [WishlistController::class, 'index']);
Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);
Route::post('/wishlist/sync', [WishlistController::class, 'sync']);
Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy']);

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

        // Categories CRUD & Reordering
        Route::get('/categories', [CategoryController::class, 'index'])->middleware('permission:categories.manage');
        Route::post('/categories', [CategoryController::class, 'store'])->middleware('permission:categories.manage');
        Route::post('/categories/reorder', [CategoryController::class, 'reorder'])->middleware('permission:categories.manage');
        Route::post('/categories/upload-image', [CategoryController::class, 'uploadImage'])->middleware('permission:categories.manage');
        Route::get('/categories/{category}', [CategoryController::class, 'show'])->middleware('permission:categories.manage');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('permission:categories.manage');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.manage');

        // Menus & Mega Menu Builder
        Route::get('/menus', [MenuController::class, 'index'])->middleware('permission:menus.manage');
        Route::post('/menus', [MenuController::class, 'store'])->middleware('permission:menus.manage');
        Route::post('/menus/upload-image', [MenuController::class, 'uploadImage'])->middleware('permission:menus.manage');
        Route::get('/menus/{menu}', [MenuController::class, 'show'])->middleware('permission:menus.manage');
        Route::put('/menus/{menu}', [MenuController::class, 'update'])->middleware('permission:menus.manage');
        Route::delete('/menus/{menu}', [MenuController::class, 'destroy'])->middleware('permission:menus.manage');
        Route::post('/menus/{menu}/items', [MenuController::class, 'storeItem'])->middleware('permission:menus.manage');
        Route::put('/menus/items/{item}', [MenuController::class, 'updateItem'])->middleware('permission:menus.manage');
        Route::delete('/menus/items/{item}', [MenuController::class, 'destroyItem'])->middleware('permission:menus.manage');
        Route::post('/menus/{menu}/reorder', [MenuController::class, 'reorderItems'])->middleware('permission:menus.manage');

        // Products Catalog & Advanced Features
        Route::get('/products', [ProductController::class, 'index'])->middleware('permission:products.view');
        Route::post('/products', [ProductController::class, 'store'])->middleware('permission:products.create');
        Route::get('/products/export-csv', [ProductController::class, 'exportCsv'])->middleware('permission:products.view');
        Route::post('/products/import-csv', [ProductController::class, 'importCsv'])->middleware('permission:products.create');
        Route::post('/products/bulk-action', [ProductController::class, 'bulkAction'])->middleware('permission:products.update');
        Route::post('/products/upload-image', [ProductController::class, 'uploadImage'])->middleware('permission:products.update');
        Route::post('/products/upload-video', [ProductController::class, 'uploadVideo'])->middleware('permission:products.update');
        Route::get('/products/{product}', [ProductController::class, 'show'])->middleware('permission:products.view');
        Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('permission:products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.delete');
        Route::post('/products/{product}/duplicate', [ProductController::class, 'duplicate'])->middleware('permission:products.create');
        Route::post('/products/{product}/variants/bulk-edit', [ProductController::class, 'bulkEditVariants'])->middleware('permission:products.update');
    });
});

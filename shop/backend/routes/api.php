<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\MarketingController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SystemAndExtrasController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\ChatController as PublicChatController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CustomerAccountController;
use App\Http\Controllers\Api\PublicCategoryController;
use App\Http\Controllers\Api\PublicMarketingController;
use App\Http\Controllers\Api\PublicMenuController;
use App\Http\Controllers\Api\PublicProductController;
use App\Http\Controllers\Api\PublicSettingController;
use App\Http\Controllers\Api\StorefrontExtrasController;
use App\Http\Controllers\Api\StripeWebhookController;
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
Route::post('/reviews/upload-photo', [PublicProductController::class, 'uploadReviewPhoto']);
Route::post('/reviews/{id}/helpful', [PublicProductController::class, 'voteHelpful']);

// Public Marketing, CMS Pages & Newsletter
Route::get('/hero-slides', [PublicMarketingController::class, 'heroSlides']);
Route::get('/popups', [PublicMarketingController::class, 'popups']);
Route::post('/popups/{popup}/track', [PublicMarketingController::class, 'trackPopup']);
Route::get('/pages', [PublicMarketingController::class, 'pages']);
Route::get('/pages/{slug}', [PublicMarketingController::class, 'pageShow']);
Route::get('/reviews/featured', [PublicMarketingController::class, 'featuredReviews']);
Route::post('/newsletter/subscribe', [PublicMarketingController::class, 'subscribe'])->middleware('throttle:15,1');
Route::post('/newsletter/unsubscribe', [StorefrontExtrasController::class, 'unsubscribeNewsletter'])->middleware('throttle:15,1');
Route::post('/contact', [StorefrontExtrasController::class, 'submitContact'])->middleware('throttle:10,1');
Route::post('/products/{product}/back-in-stock', [StorefrontExtrasController::class, 'subscribeBackInStock'])->middleware('throttle:15,1');
Route::post('/orders/track', [StorefrontExtrasController::class, 'trackOrder'])->middleware('throttle:20,1');

// Live Chat Visitor Endpoints (Gentle Polling without WebSockets)
Route::get('/chat/messages', [PublicChatController::class, 'index'])->middleware('throttle:90,1');
Route::post('/chat/messages', [PublicChatController::class, 'store'])->middleware('throttle:30,1');
Route::post('/chat/upload-attachment', [PublicChatController::class, 'uploadAttachment'])->middleware('throttle:15,1');
Route::post('/chat/merge', [PublicChatController::class, 'merge']);

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

// Checkout & Stripe Webhook Routes
Route::get('/checkout/addresses', [CheckoutController::class, 'addresses']);
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:15,1');
Route::get('/checkout/verify', [CheckoutController::class, 'verify']);
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

// Guest Auth Routes
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');

// Authenticated User Routes (Customers & Staff)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'changePassword']);

    // Customer Account Area (Strictly user-scoped)
    Route::prefix('account')->group(function () {
        Route::get('/overview', [CustomerAccountController::class, 'overview']);
        Route::get('/orders', [CustomerAccountController::class, 'orders']);
        Route::get('/orders/{orderNumber}', [CustomerAccountController::class, 'orderShow']);
        Route::post('/orders/{orderNumber}/cancel-request', [CustomerAccountController::class, 'requestCancelOrder']);
        Route::get('/addresses', [CustomerAccountController::class, 'addresses']);
        Route::post('/addresses', [CustomerAccountController::class, 'storeAddress']);
        Route::put('/addresses/{id}', [CustomerAccountController::class, 'updateAddress']);
        Route::delete('/addresses/{id}', [CustomerAccountController::class, 'destroyAddress']);
        Route::post('/addresses/{id}/default', [CustomerAccountController::class, 'setDefaultAddress']);
        Route::get('/reviews', [CustomerAccountController::class, 'reviews']);
        Route::delete('/', [CustomerAccountController::class, 'deleteAccount']);
    });

    // Admin Routes
    Route::prefix('admin')->group(function () {
        // Dashboard Home & Notifications (Staff only; revenue gated by finance.view)
        Route::get('/dashboard', [DashboardController::class, 'index']);

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
        Route::get('/finance/export-csv', [FinanceController::class, 'exportCsv'])->middleware('permission:finance.view');

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
        Route::get('/brands', [ProductController::class, 'brands'])->middleware('permission:products.view');
        Route::get('/products', [ProductController::class, 'index'])->middleware('permission:products.view');
        Route::post('/products', [ProductController::class, 'store'])->middleware('permission:products.create');
        Route::get('/products/export-csv', [ProductController::class, 'exportCsv'])->middleware('permission:products.view');
        Route::post('/products/import-csv', [ProductController::class, 'importCsv'])->middleware('permission:products.create');
        Route::post('/products/bulk-action', [ProductController::class, 'bulkAction'])->middleware('permission:products.update');
        Route::post('/products/upload-image', [ProductController::class, 'uploadImage'])->middleware('permission:products.update');
        Route::post('/products/upload-video', [ProductController::class, 'uploadVideo'])->middleware('permission:products.update');
        Route::get('/products/{product}', [ProductController::class, 'show'])->middleware('permission:products.view');
        Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('permission:products.update');
        Route::patch('/products/{product}/quick-toggle', [ProductController::class, 'quickToggle'])->middleware('permission:products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.delete');
        Route::post('/products/{product}/duplicate', [ProductController::class, 'duplicate'])->middleware('permission:products.create');
        Route::post('/products/{product}/variants/bulk-edit', [ProductController::class, 'bulkEditVariants'])->middleware('permission:products.update');

        // Orders, Fulfillment, Refunds & Low-Stock Inventory
        Route::get('/orders', [OrderController::class, 'index'])->middleware('permission:orders.view');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->middleware('permission:orders.view');
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->middleware('permission:orders.update');
        Route::patch('/orders/{order}/tracking', [OrderController::class, 'updateTracking'])->middleware('permission:orders.update');
        Route::post('/orders/{order}/notes', [OrderController::class, 'addNote'])->middleware('permission:orders.update');
        Route::post('/orders/{order}/resend-confirmation', [OrderController::class, 'resendConfirmation'])->middleware('permission:orders.update');
        Route::post('/orders/{order}/refund', [CheckoutController::class, 'refund'])->middleware('permission:orders.refund');

        // Inventory / Low-Stock Management
        Route::get('/inventory/low-stock', [OrderController::class, 'lowStock'])->middleware('permission:orders.view|products.view');
        Route::patch('/inventory/stock', [OrderController::class, 'updateStock'])->middleware('permission:orders.update|products.update');
        Route::post('/inventory/low-stock-alert', [OrderController::class, 'sendLowStockAlert'])->middleware('permission:orders.update|products.update');

        // Customer Directory & Profiles
        Route::get('/customers', [CustomerController::class, 'index'])->middleware('permission:users.view');
        Route::get('/customers/export-csv', [CustomerController::class, 'exportCsv'])->middleware('permission:users.view');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware('permission:users.view');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->middleware('permission:users.update|orders.update');

        // Hero Slides & Home Banners / Trust Points
        Route::get('/hero-slides', [MarketingController::class, 'heroIndex'])->middleware('permission:hero.manage');
        Route::post('/hero-slides', [MarketingController::class, 'heroStore'])->middleware('permission:hero.manage');
        Route::post('/hero-slides/reorder', [MarketingController::class, 'heroReorder'])->middleware('permission:hero.manage');
        Route::post('/hero-slides/upload-image', [MarketingController::class, 'uploadImage'])->middleware('permission:hero.manage|popups.manage');
        Route::put('/hero-slides/{slide}', [MarketingController::class, 'heroUpdate'])->middleware('permission:hero.manage');
        Route::delete('/hero-slides/{slide}', [MarketingController::class, 'heroDestroy'])->middleware('permission:hero.manage');

        Route::get('/marketing/home-blocks', [MarketingController::class, 'homeBlocksIndex'])->middleware('permission:hero.manage|settings.view');
        Route::put('/marketing/home-blocks', [MarketingController::class, 'homeBlocksUpdate'])->middleware('permission:hero.manage|settings.update');

        // Promotional Popups
        Route::get('/popups', [MarketingController::class, 'popupsIndex'])->middleware('permission:popups.manage');
        Route::post('/popups', [MarketingController::class, 'popupsStore'])->middleware('permission:popups.manage');
        Route::post('/popups/upload-image', [MarketingController::class, 'uploadImage'])->middleware('permission:popups.manage|hero.manage');
        Route::put('/popups/{popup}', [MarketingController::class, 'popupsUpdate'])->middleware('permission:popups.manage');
        Route::delete('/popups/{popup}', [MarketingController::class, 'popupsDestroy'])->middleware('permission:popups.manage');

        // CMS Pages
        Route::get('/pages', [PageController::class, 'index'])->middleware('permission:pages.manage|menus.manage');
        Route::post('/pages', [PageController::class, 'store'])->middleware('permission:pages.manage');
        Route::get('/pages/{page}', [PageController::class, 'show'])->middleware('permission:pages.manage');
        Route::put('/pages/{page}', [PageController::class, 'update'])->middleware('permission:pages.manage');
        Route::delete('/pages/{page}', [PageController::class, 'destroy'])->middleware('permission:pages.manage');

        // Product Reviews Management (Moderation, Custom Admin Reviews, Replies & CSV Import)
        Route::get('/reviews', [ReviewController::class, 'index'])->middleware('permission:reviews.manage');
        Route::post('/reviews', [ReviewController::class, 'store'])->middleware('permission:reviews.manage');
        Route::post('/reviews/import-csv', [ReviewController::class, 'importCsv'])->middleware('permission:reviews.manage');
        Route::post('/reviews/upload-photo', [ReviewController::class, 'uploadPhoto'])->middleware('permission:reviews.manage');
        Route::put('/reviews/{review}', [ReviewController::class, 'update'])->middleware('permission:reviews.manage');
        Route::patch('/reviews/{review}/status', [ReviewController::class, 'updateStatus'])->middleware('permission:reviews.manage');
        Route::post('/reviews/{review}/reply', [ReviewController::class, 'reply'])->middleware('permission:reviews.manage');
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->middleware('permission:reviews.manage');

        // Live Chat Inbox (Staff Conversation Management, Visitor Context & Canned Responses)
        Route::get('/chat/conversations', [AdminChatController::class, 'index'])->middleware('permission:chat.manage');
        Route::get('/chat/conversations/{conversation}', [AdminChatController::class, 'show'])->middleware('permission:chat.manage');
        Route::post('/chat/conversations/{conversation}/messages', [AdminChatController::class, 'sendMessage'])->middleware('permission:chat.manage');
        Route::patch('/chat/conversations/{conversation}', [AdminChatController::class, 'update'])->middleware('permission:chat.manage');
        Route::get('/chat/canned-responses', [AdminChatController::class, 'cannedResponses'])->middleware('permission:chat.manage');
        Route::put('/chat/canned-responses', [AdminChatController::class, 'updateCannedResponses'])->middleware('permission:chat.manage');
        Route::post('/chat/upload-attachment', [AdminChatController::class, 'uploadAttachment'])->middleware('permission:chat.manage');

        // Email Templates, Previews, Test Dispatches & Delivery Logs (Hostinger SMTP)
        Route::get('/email-templates', [EmailTemplateController::class, 'index'])->middleware('permission:mail.manage|settings.view');
        Route::put('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->middleware('permission:mail.manage');
        Route::post('/email-templates/{emailTemplate}/reset', [EmailTemplateController::class, 'resetToDefault'])->middleware('permission:mail.manage');
        Route::post('/email-templates/{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])->middleware('permission:mail.manage|settings.view');
        Route::post('/email-templates/{emailTemplate}/test', [EmailTemplateController::class, 'sendTest'])->middleware('permission:mail.manage');
        Route::get('/email-logs', [EmailTemplateController::class, 'logs'])->middleware('permission:mail.manage|settings.view');
        Route::post('/email-logs/{emailLog}/resend', [EmailTemplateController::class, 'resendLog'])->middleware('permission:mail.manage');

        // Brands & Labels CRUD
        Route::get('/brands-manage', [BrandController::class, 'index'])->middleware('permission:brands.manage|products.view');
        Route::post('/brands-manage', [BrandController::class, 'store'])->middleware('permission:brands.manage');
        Route::put('/brands-manage/{brand}', [BrandController::class, 'update'])->middleware('permission:brands.manage');
        Route::delete('/brands-manage/{brand}', [BrandController::class, 'destroy'])->middleware('permission:brands.manage');

        // Coupons & Discounts CRUD (Percent, Fixed, Free Shipping, Buy X get Y%)
        Route::get('/coupons', [CouponController::class, 'index'])->middleware('permission:coupons.manage');
        Route::post('/coupons', [CouponController::class, 'store'])->middleware('permission:coupons.manage');
        Route::put('/coupons/{coupon}', [CouponController::class, 'update'])->middleware('permission:coupons.manage');
        Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy'])->middleware('permission:coupons.manage');

        // Activity Log Viewer, Quick Command Palette Search & One-Click DB Backup
        Route::get('/activity-logs', [SystemAndExtrasController::class, 'activityLogs'])->middleware('permission:activity.view');
        Route::get('/quick-search', [SystemAndExtrasController::class, 'quickSearch']);
        Route::get('/system/backup-db', [SystemAndExtrasController::class, 'exportDatabaseBackup'])->middleware('role:super_admin');

        // Newsletter Admin List & CSV Export
        Route::get('/newsletter', [SystemAndExtrasController::class, 'newsletterIndex'])->middleware('permission:popups.manage|settings.view');
        Route::get('/newsletter/export-csv', [SystemAndExtrasController::class, 'newsletterExportCsv'])->middleware('permission:popups.manage|settings.view');
        Route::delete('/newsletter/{subscriber}', [SystemAndExtrasController::class, 'newsletterDestroy'])->middleware('permission:popups.manage|settings.update');

        // Contact Form Messages Inbox
        Route::get('/contact-messages', [SystemAndExtrasController::class, 'contactMessagesIndex'])->middleware('permission:chat.manage|settings.view');
        Route::patch('/contact-messages/{contactMessage}', [SystemAndExtrasController::class, 'contactMessageUpdate'])->middleware('permission:chat.manage|settings.update');
        Route::delete('/contact-messages/{contactMessage}', [SystemAndExtrasController::class, 'contactMessageDestroy'])->middleware('permission:chat.manage|settings.update');

        // 301 URL Redirect Manager
        Route::get('/redirects', [SystemAndExtrasController::class, 'redirectsIndex'])->middleware('permission:settings.view|pages.manage');
        Route::post('/redirects', [SystemAndExtrasController::class, 'redirectsStore'])->middleware('permission:settings.update|pages.manage');
        Route::delete('/redirects/{redirect}', [SystemAndExtrasController::class, 'redirectsDestroy'])->middleware('permission:settings.update|pages.manage');
    });
});

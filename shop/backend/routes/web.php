<?php

use App\Http\Controllers\Api\StorefrontExtrasController;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\UrlRedirect;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

Route::get('/sitemap.xml', [StorefrontExtrasController::class, 'sitemap']);
Route::get('/robots.txt', [StorefrontExtrasController::class, 'robots']);

Route::get('/{any?}', function (Request $request, ?string $any = null) {
    $path = '/'.ltrim((string) $any, '/');

    // 1. Check 301 / 302 URL Redirect Rules
    try {
        if ($path !== '/' && Schema::hasTable('url_redirects')) {
            $redirect = UrlRedirect::where('source_path', $path)
                ->where('is_active', true)
                ->first();

            if ($redirect) {
                $redirect->increment('hits_count');

                return redirect($redirect->target_path, (int) ($redirect->status_code ?: 301));
            }
        }
    } catch (Throwable) {
        // Ignore if database is not yet migrated
    }

    $spaIndex = public_path('app/index.html');
    if (! file_exists($spaIndex)) {
        $spaIndex = public_path('index.html');
    }

    if (! file_exists($spaIndex)) {
        return response()->json([
            'message' => 'E-commerce API is running. Build frontend or start Vite dev server.',
            'health' => url('/api/health'),
        ]);
    }

    $html = file_get_contents($spaIndex);

    // 2. Server-Side SEO Meta & JSON-LD Injection for Crawlers & Social Previews
    try {
        $siteName = (string) SettingService::get('site_name', 'Atelier');
        $defaultDesc = (string) SettingService::get('seo_description', 'Thoughtfully crafted essentials for modern living.');
        $defaultImg = (string) SettingService::get('seo_social_image', '');
        $currency = (string) SettingService::get('currency_code', 'USD');
        $canonicalUrl = url($path === '/' ? '/' : $path);

        $seoTitle = (string) SettingService::get('seo_title', $siteName);
        $seoDesc = $defaultDesc;
        $seoImage = $defaultImg;
        $ogType = 'website';
        $jsonLd = null;

        if (preg_match('#^/products?/([^/]+)$#', $path, $matches)) {
            $slug = $matches[1];
            $product = Product::with(['primaryImage', 'category', 'brand'])
                ->where('slug', $slug)
                ->where('status', 'active')
                ->first();

            if ($product) {
                $seoTitle = ($product->seo_title ?: $product->name)." — {$siteName}";
                $seoDesc = $product->seo_description
                    ?: Str::limit(trim(strip_tags((string) ($product->short_description ?: $product->description))), 160);
                $seoImage = $product->seo_social_image
                    ?: ($product->primaryImage?->url ?: $defaultImg);
                $ogType = 'product';

                $inStock = ! $product->track_stock || (int) $product->stock_qty > 0 || $product->allow_backorder;
                $ldData = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Product',
                    'name' => $product->name,
                    'description' => $seoDesc,
                    'sku' => $product->sku,
                    'image' => $seoImage ? [$seoImage] : [],
                    'brand' => [
                        '@type' => 'Brand',
                        'name' => $product->brand?->name ?: $siteName,
                    ],
                    'offers' => [
                        '@type' => 'Offer',
                        'url' => $canonicalUrl,
                        'priceCurrency' => $currency,
                        'price' => number_format((float) $product->base_price, 2, '.', ''),
                        'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    ],
                ];

                if ((int) $product->reviews_count > 0) {
                    $ldData['aggregateRating'] = [
                        '@type' => 'AggregateRating',
                        'ratingValue' => number_format((float) $product->rating_avg, 1, '.', ''),
                        'reviewCount' => (int) $product->reviews_count,
                    ];
                }

                $jsonLd = json_encode($ldData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        } elseif (preg_match('#^/category/([^/]+)$#', $path, $matches)) {
            $slug = $matches[1];
            $category = Category::where('slug', $slug)->where('is_active', true)->first();

            if ($category) {
                $seoTitle = ($category->seo_title ?: $category->name)." — {$siteName}";
                $seoDesc = $category->seo_description
                    ?: Str::limit(trim(strip_tags((string) $category->description)), 160)
                    ?: "Shop {$category->name} at {$siteName}.";
                $seoImage = $category->image ?: $defaultImg;

                $jsonLd = json_encode([
                    '@context' => 'https://schema.org',
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Home',
                            'item' => url('/'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => $category->name,
                            'item' => $canonicalUrl,
                        ],
                    ],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        } elseif (preg_match('#^/page/([^/]+)$#', $path, $matches)) {
            $slug = $matches[1];
            $page = Page::where('slug', $slug)->where('is_published', true)->first();
            if ($page) {
                $seoTitle = ($page->seo_title ?: $page->title)." — {$siteName}";
                $seoDesc = $page->seo_description ?: Str::limit(trim(strip_tags((string) $page->content)), 160);
            }
        }

        $metaTags = implode("\n    ", array_filter([
            '<meta name="description" content="'.e($seoDesc).'">',
            '<link rel="canonical" href="'.e($canonicalUrl).'">',
            '<meta property="og:title" content="'.e($seoTitle).'">',
            '<meta property="og:description" content="'.e($seoDesc).'">',
            '<meta property="og:type" content="'.e($ogType).'">',
            '<meta property="og:url" content="'.e($canonicalUrl).'">',
            '<meta property="og:site_name" content="'.e($siteName).'">',
            $seoImage ? '<meta property="og:image" content="'.e($seoImage).'">' : null,
            '<meta name="twitter:card" content="summary_large_image">',
            '<meta name="twitter:title" content="'.e($seoTitle).'">',
            '<meta name="twitter:description" content="'.e($seoDesc).'">',
            $jsonLd ? '<script type="application/ld+json">'.$jsonLd.'</script>' : null,
        ]));

        // Replace default <title> and inject meta tags before </head>
        $html = preg_replace('/<title>.*?<\/title>/is', '<title>'.e($seoTitle).'</title>', $html, 1);
        $html = str_replace('</head>', "    {$metaTags}\n  </head>", $html);
    } catch (Throwable) {
        // Fallback to unmodified SPA HTML shell
    }

    return response($html, 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
    ]);
})->where('any', '^(?!api|sanctum|sitemap\.xml|robots\.txt).*$');

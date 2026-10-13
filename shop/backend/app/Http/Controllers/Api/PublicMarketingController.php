<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use App\Models\NewsletterSubscriber;
use App\Models\Page;
use App\Models\Popup;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicMarketingController extends Controller
{
    /**
     * Get active hero slides for storefront home slider.
     */
    public function heroSlides(): JsonResponse
    {
        $slides = HeroSlide::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => $slides,
        ]);
    }

    /**
     * Get active popups for storefront trigger manager.
     */
    public function popups(): JsonResponse
    {
        $popups = Popup::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => $popups,
        ]);
    }

    /**
     * Record a view, click, or signup event on a popup.
     */
    public function trackPopup(Request $request, Popup $popup): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:view,click,signup',
        ]);

        if ($validated['action'] === 'view') {
            $popup->increment('views_count');
        } elseif ($validated['action'] === 'click') {
            $popup->increment('clicks_count');
        } elseif ($validated['action'] === 'signup') {
            $popup->increment('signups_count');
        }

        return response()->json([
            'status' => 'ok',
            'data' => [
                'id' => $popup->id,
                'views_count' => (int) $popup->views_count,
                'clicks_count' => (int) $popup->clicks_count,
                'signups_count' => (int) $popup->signups_count,
            ],
        ]);
    }

    /**
     * Get calm strip of 3-4 featured reviews for storefront home page.
     */
    public function featuredReviews(): JsonResponse
    {
        $reviews = Review::where('is_approved', true)
            ->where('rating', '>=', 4)
            ->with(['product:id,name,slug'])
            ->latest('review_date')
            ->take(4)
            ->get()
            ->map(fn (Review $r) => [
                'id' => $r->id,
                'author_name' => $r->author_name,
                'rating' => (int) $r->rating,
                'title' => $r->title,
                'body' => $r->body,
                'review_date' => $r->review_date ? $r->review_date->format('M d, Y') : null,
                'product' => $r->product ? [
                    'name' => $r->product->name,
                    'slug' => $r->product->slug,
                ] : null,
            ]);

        return response()->json([
            'data' => $reviews,
        ]);
    }

    /**
     * Subscribe email to newsletter.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:150',
            'popup_id' => 'nullable|integer|exists:popups,id',
        ]);

        NewsletterSubscriber::updateOrCreate(
            ['email' => strtolower(trim($validated['email']))],
            ['is_active' => true, 'subscribed_at' => now()]
        );

        if (! empty($validated['popup_id'])) {
            Popup::where('id', $validated['popup_id'])->increment('signups_count');
        }

        return response()->json([
            'message' => 'Thank you for subscribing! Your welcome voucher will arrive shortly.',
        ]);
    }

    /**
     * List all published CMS pages for navigation / footer links.
     */
    public function pages(): JsonResponse
    {
        $pages = Cache::remember('public_cms_pages_list', 3600, function () {
            return Page::where('is_published', true)
                ->orderBy('title')
                ->get(['id', 'title', 'slug', 'seo_title', 'seo_description', 'updated_at']);
        });

        return response()->json([
            'data' => $pages,
        ]);
    }

    /**
     * Retrieve a single published CMS page by slug.
     */
    public function pageShow(string $slug): JsonResponse
    {
        $page = Cache::remember("public_cms_page_{$slug}", 3600, function () use ($slug) {
            return Page::where('slug', $slug)
                ->where('is_published', true)
                ->first();
        });

        if (! $page) {
            return response()->json([
                'message' => 'Page not found.',
            ], 404);
        }

        return response()->json([
            'data' => $page,
        ]);
    }
}

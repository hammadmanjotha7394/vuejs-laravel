<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use App\Models\NewsletterSubscriber;
use App\Models\Popup;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        ]);

        NewsletterSubscriber::updateOrCreate(
            ['email' => strtolower(trim($validated['email']))],
            ['is_active' => true, 'subscribed_at' => now()]
        );

        return response()->json([
            'message' => 'Thank you for subscribing! Your welcome voucher will arrive shortly.',
        ]);
    }
}

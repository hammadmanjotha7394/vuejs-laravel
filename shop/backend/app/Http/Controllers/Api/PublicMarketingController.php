<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Models\Popup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicMarketingController extends Controller
{
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

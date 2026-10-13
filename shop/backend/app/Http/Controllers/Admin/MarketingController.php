<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\HeroSlide;
use App\Models\Popup;
use App\Services\ImageService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MarketingController extends Controller
{
    /**
     * List all hero slides ordered by sort_order.
     */
    public function heroIndex(): JsonResponse
    {
        $slides = HeroSlide::orderBy('sort_order')->orderBy('id')->get();

        return response()->json([
            'data' => $slides,
        ]);
    }

    /**
     * Create a new hero slide.
     */
    public function heroStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'subtitle' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:500',
            'image_desktop' => 'required|string|max:500',
            'image_mobile' => 'nullable|string|max:500',
            'text_position' => 'nullable|in:left,center,right',
            'overlay_opacity' => 'nullable|numeric|min:0|max:0.95',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if (! isset($validated['sort_order'])) {
            $validated['sort_order'] = (int) HeroSlide::max('sort_order') + 1;
        }
        $validated['text_position'] = $validated['text_position'] ?? 'left';
        $validated['overlay_opacity'] = isset($validated['overlay_opacity']) ? round((float) $validated['overlay_opacity'], 2) : 0.25;
        $validated['is_active'] = array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true;

        $slide = HeroSlide::create($validated);
        Cache::forget('storefront_hero_slides');

        ActivityLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => 'hero_slide.created',
            'subject_type' => HeroSlide::class,
            'subject_id' => $slide->id,
            'properties_json' => ['title' => $slide->title],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Hero slide created successfully.',
            'data' => $slide,
        ], 201);
    }

    /**
     * Update an existing hero slide.
     */
    public function heroUpdate(Request $request, HeroSlide $slide): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:200',
            'subtitle' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:500',
            'image_desktop' => 'sometimes|required|string|max:500',
            'image_mobile' => 'nullable|string|max:500',
            'text_position' => 'nullable|in:left,center,right',
            'overlay_opacity' => 'nullable|numeric|min:0|max:0.95',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if (isset($validated['overlay_opacity'])) {
            $validated['overlay_opacity'] = round((float) $validated['overlay_opacity'], 2);
        }

        $slide->update($validated);
        Cache::forget('storefront_hero_slides');

        return response()->json([
            'message' => 'Hero slide updated successfully.',
            'data' => $slide->fresh(),
        ]);
    }

    /**
     * Delete a hero slide.
     */
    public function heroDestroy(Request $request, HeroSlide $slide): JsonResponse
    {
        $slide->delete();
        Cache::forget('storefront_hero_slides');

        return response()->json([
            'message' => 'Hero slide deleted.',
        ]);
    }

    /**
     * Reorder hero slides.
     */
    public function heroReorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:hero_slides,id',
        ]);

        foreach ($validated['ids'] as $index => $id) {
            HeroSlide::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        Cache::forget('storefront_hero_slides');

        return response()->json([
            'message' => 'Hero slides reordered.',
            'data' => HeroSlide::orderBy('sort_order')->get(),
        ]);
    }

    /**
     * Upload an image for hero slides, popups, or promo banners.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp,svg|max:8192',
        ]);

        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension());

        if ($ext === 'svg') {
            $svgContent = file_get_contents($file->getRealPath());
            if (! SettingService::sanitizeSvg((string) $svgContent)) {
                return response()->json([
                    'message' => 'SVG contains unsafe scripts or event handlers.',
                ], 422);
            }
            $filename = 'marketing/'.Str::uuid().'.svg';
            Storage::disk('public')->put($filename, $svgContent);
            $url = Storage::disk('public')->url($filename);

            return response()->json([
                'path' => $filename,
                'url' => $url,
            ]);
        }

        $processed = ImageService::processProductImage($file, 'marketing');

        return response()->json([
            'path' => $processed['large_path'],
            'url' => $processed['large_url'],
            'thumb_url' => $processed['thumb_url'],
        ]);
    }

    /**
     * List all promotional popups with analytics counters.
     */
    public function popupsIndex(): JsonResponse
    {
        $popups = Popup::orderBy('sort_order')->orderByDesc('id')->get();

        return response()->json([
            'data' => $popups,
        ]);
    }

    /**
     * Create a new promotional popup.
     */
    public function popupsStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'type' => 'required|in:image,content,newsletter,announcement',
            'title' => 'nullable|string|max:200',
            'body' => 'nullable|string|max:2000',
            'image' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:500',
            'trigger' => 'required|in:on_load,delay,scroll,exit_intent',
            'trigger_value' => 'nullable|integer|min:0|max:300',
            'show_on_pages_json' => 'nullable|array',
            'frequency' => 'required|in:once_per_session,once_per_day,always,once_ever',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['trigger_value'] = $validated['trigger_value'] ?? 5;
        $validated['show_on_pages_json'] = $validated['show_on_pages_json'] ?? ['all'];
        $validated['is_active'] = array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true;
        $validated['sort_order'] = $validated['sort_order'] ?? ((int) Popup::max('sort_order') + 1);

        $popup = Popup::create($validated);
        Cache::forget('storefront_popups');

        ActivityLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => 'popup.created',
            'subject_type' => Popup::class,
            'subject_id' => $popup->id,
            'properties_json' => ['name' => $popup->name, 'type' => $popup->type],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Popup created successfully.',
            'data' => $popup,
        ], 201);
    }

    /**
     * Update an existing promotional popup.
     */
    public function popupsUpdate(Request $request, Popup $popup): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'type' => 'sometimes|required|in:image,content,newsletter,announcement',
            'title' => 'nullable|string|max:200',
            'body' => 'nullable|string|max:2000',
            'image' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:500',
            'trigger' => 'sometimes|required|in:on_load,delay,scroll,exit_intent',
            'trigger_value' => 'nullable|integer|min:0|max:300',
            'show_on_pages_json' => 'nullable|array',
            'frequency' => 'sometimes|required|in:once_per_session,once_per_day,always,once_ever',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $popup->update($validated);
        Cache::forget('storefront_popups');

        return response()->json([
            'message' => 'Popup updated successfully.',
            'data' => $popup->fresh(),
        ]);
    }

    /**
     * Delete a promotional popup.
     */
    public function popupsDestroy(Popup $popup): JsonResponse
    {
        $popup->delete();
        Cache::forget('storefront_popups');

        return response()->json([
            'message' => 'Popup deleted.',
        ]);
    }

    /**
     * Retrieve editable home page Promo Banners and 4 Trust Points.
     */
    public function homeBlocksIndex(): JsonResponse
    {
        $defaultBanners = [
            [
                'tag' => 'Artisan Textiles',
                'title' => 'Organic Flax Linen',
                'description' => 'Breathable shirting and washed duvet sets tailored for longevity.',
                'button_text' => 'Shop Collection',
                'button_url' => '/category/women',
                'image' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=1000&q=80',
            ],
            [
                'tag' => 'Architectural Lighting',
                'title' => 'Warm Table Lamps',
                'description' => 'Cast solid brass with warm dimmed tones for tactile sanctuary spaces.',
                'button_text' => 'Explore Homeware',
                'button_url' => '/category/home-living',
                'image' => 'https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=1000&q=80',
            ],
        ];

        $defaultTrustPoints = [
            [
                'icon' => 'truck',
                'title' => 'Carbon-Neutral Delivery',
                'desc' => 'Free standard shipping on all domestic orders over $75.',
                'link' => '/page/shipping-returns',
            ],
            [
                'icon' => 'rotate-ccw',
                'title' => '30-Day Calm Return',
                'desc' => 'Prepaid recyclable labels and effortless exchange processing.',
                'link' => '/page/shipping-returns',
            ],
            [
                'icon' => 'shield-check',
                'title' => 'Direct Artisan Provenance',
                'desc' => 'Collaborations with small family-owned European & Japanese workshops.',
                'link' => '/page/about',
            ],
            [
                'icon' => 'headphones',
                'title' => 'Dedicated Human Support',
                'desc' => 'Reach our studio concierge 7 days a week for honest guidance.',
                'link' => '/page/contact',
            ],
        ];

        $banners = SettingService::getJson('promo_banners', []);
        $trustPoints = SettingService::getJson('trust_points', []);

        return response()->json([
            'data' => [
                'promo_banners' => ! empty($banners) ? $banners : $defaultBanners,
                'trust_points' => ! empty($trustPoints) ? $trustPoints : $defaultTrustPoints,
            ],
        ]);
    }

    /**
     * Save editable home page Promo Banners and Trust Points and clear public settings cache.
     */
    public function homeBlocksUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'promo_banners' => 'nullable|array|max:6',
            'promo_banners.*.tag' => 'nullable|string|max:80',
            'promo_banners.*.title' => 'required|string|max:160',
            'promo_banners.*.description' => 'nullable|string|max:400',
            'promo_banners.*.button_text' => 'nullable|string|max:80',
            'promo_banners.*.button_url' => 'nullable|string|max:300',
            'promo_banners.*.image' => 'required|string|max:500',
            'trust_points' => 'nullable|array|max:4',
            'trust_points.*.icon' => 'nullable|string|max:50',
            'trust_points.*.title' => 'required|string|max:120',
            'trust_points.*.desc' => 'required|string|max:300',
            'trust_points.*.link' => 'nullable|string|max:300',
            'trust_points.*.image' => 'nullable|string|max:500',
        ]);

        $toSave = [];
        if (array_key_exists('promo_banners', $validated)) {
            $toSave['promo_banners'] = $validated['promo_banners'] ?? [];
        }
        if (array_key_exists('trust_points', $validated)) {
            $toSave['trust_points'] = $validated['trust_points'] ?? [];
        }

        SettingService::setMany($toSave, $request->user(), $request->ip());

        return response()->json([
            'message' => 'Home banners and trust points saved.',
            'data' => [
                'promo_banners' => SettingService::getJson('promo_banners', []),
                'trust_points' => SettingService::getJson('trust_points', []),
            ],
        ]);
    }
}

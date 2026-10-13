<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use App\Models\EmailLog;
use App\Services\EmailService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Throwable;

class SettingController extends Controller
{
    /**
     * Get all administrative settings with secrets masked.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => SettingService::getAdminSettings(),
        ]);
    }

    /**
     * Update general, store, announcement, footer, social, shipping, and SEO settings.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_name' => 'nullable|string|max:100',
            'site_tagline' => 'nullable|string|max:255',
            'logo_url' => 'nullable|string|max:500',
            'favicon_url' => 'nullable|string|max:500',
            'brand_color' => 'nullable|string|max:20',
            'support_email' => 'nullable|email|max:100',
            'support_phone' => 'nullable|string|max:50',
            'store_address' => 'nullable|string|max:255',
            'currency_code' => 'nullable|string|max:10',
            'currency_symbol' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',

            // Announcement
            'announcement_enabled' => 'nullable|in:0,1,true,false',
            'announcement_text' => 'nullable|string|max:500',
            'announcement_link' => 'nullable|string|max:255',
            'announcement_bg' => 'nullable|string|max:20',
            'announcement_text_color' => 'nullable|string|max:20',
            'announcement_dismissible' => 'nullable|in:0,1,true,false',
            'announcement_start_date' => 'nullable|date',
            'announcement_end_date' => 'nullable|date',

            // Social
            'social_instagram' => 'nullable|string|max:255',
            'social_facebook' => 'nullable|string|max:255',
            'social_tiktok' => 'nullable|string|max:255',
            'social_youtube' => 'nullable|string|max:255',
            'social_x' => 'nullable|string|max:255',
            'social_whatsapp' => 'nullable|string|max:50',

            // Footer
            'footer_tagline' => 'nullable|string|max:500',
            'footer_columns' => 'nullable',
            'payment_icons_enabled' => 'nullable|in:0,1,true,false',
            'footer_copyright' => 'nullable|string|max:255',

            // Shipping & Tax
            'default_shipping_fee' => 'nullable|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'tax_rate_percent' => 'nullable|numeric|min:0|max:100',
            'prices_include_tax' => 'nullable|in:0,1,true,false',

            // SEO
            'seo_title' => 'nullable|string|max:150',
            'seo_description' => 'nullable|string|max:500',
            'seo_social_image' => 'nullable|string|max:500',
            'google_analytics_id' => 'nullable|string|max:50',
            'meta_pixel_id' => 'nullable|string|max:50',
            'custom_head_scripts' => 'nullable|string',
            'custom_body_scripts' => 'nullable|string',

            // Store
            'maintenance_mode' => 'nullable|in:0,1,true,false',
            'maintenance_message' => 'nullable|string|max:500',
            'guest_checkout' => 'nullable|in:0,1,true,false',
            'review_moderation' => 'nullable|in:0,1,true,false',
            'require_email_verification' => 'nullable|in:0,1,true,false',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'low_stock_email' => 'nullable|email|max:100',
        ]);

        SettingService::setMany($validated, $request->user(), $request->ip());

        return response()->json([
            'message' => 'Settings updated successfully.',
            'data' => SettingService::getAdminSettings(),
        ]);
    }

    /**
     * Update Stripe integration settings.
     */
    public function updateStripe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stripe_enabled' => 'required|in:0,1,true,false',
            'stripe_mode' => 'required|string|in:test,live',
            'stripe_currency' => 'required|string|max:10',
            'stripe_publishable_key' => 'nullable|string|max:255',
            'stripe_secret_key' => 'nullable|string|max:255',
            'stripe_webhook_secret' => 'nullable|string|max:255',
        ]);

        SettingService::setMany($validated, $request->user(), $request->ip());

        return response()->json([
            'message' => 'Stripe configuration updated successfully.',
            'data' => SettingService::getAdminSettings(),
        ]);
    }

    /**
     * Test Stripe API connectivity.
     */
    public function testStripe(): JsonResponse
    {
        $secretKey = SettingService::get('stripe_secret_key');

        if (empty($secretKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe secret key is not set. Please enter and save your Stripe Secret Key before testing.',
            ]);
        }

        try {
            $stripe = new StripeClient($secretKey);
            $balance = $stripe->balance->retrieve();

            $mode = $balance->livemode ? 'Live' : 'Test';

            return response()->json([
                'success' => true,
                'message' => "Connected to Stripe successfully! Operating in {$mode} mode.",
                'livemode' => $balance->livemode,
            ]);
        } catch (ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe API Error: '.$e->getMessage(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection Error: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Update SMTP mail server settings.
     */
    public function updateMail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|integer|min:1|max:65535',
            'mail_encryption' => 'nullable|string|in:ssl,tls,none',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_from_name' => 'required|string|max:100',
            'mail_from_address' => 'required|email|max:100',
            'mail_reply_to' => 'nullable|email|max:100',
        ]);

        SettingService::setMany($validated, $request->user(), $request->ip());

        return response()->json([
            'message' => 'Email configuration updated successfully.',
            'data' => SettingService::getAdminSettings(),
        ]);
    }

    /**
     * Send an SMTP test email and log the result to email_logs.
     */
    public function testMail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'nullable|email',
        ]);

        $recipient = $request->input('email', $request->user()?->email ?? 'admin@shop.test');
        $siteName = (string) SettingService::get('site_name', 'Boutique');

        try {
            // Re-apply runtime config and purge mailer
            EmailService::applyRuntimeSmtpConfig();
            app('mail.manager')->forgetMailers();

            Mail::to($recipient)->send(new TestMail($siteName, now()->toDateTimeString()));

            EmailLog::create([
                'to' => $recipient,
                'subject' => "SMTP Verification Test: {$siteName}",
                'template_key' => 'test_email',
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Test email dispatched successfully to {$recipient}.",
            ]);
        } catch (Throwable $e) {
            EmailLog::create([
                'to' => $recipient,
                'subject' => "SMTP Verification Test: {$siteName}",
                'template_key' => 'test_email',
                'status' => 'failed',
                'error' => $e->getMessage(),
                'sent_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SMTP Dispatch Error: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Upload branding asset (logo, favicon, social image) with SVG sanitization.
     */
    public function uploadAsset(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,webp,svg,ico|max:2048',
            'type' => 'nullable|string|in:logo,favicon,social_image',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'svg') {
            $content = file_get_contents($file->getRealPath());
            if (! SettingService::sanitizeSvg($content)) {
                return response()->json([
                    'message' => 'The uploaded SVG file contains disallowed or unsafe elements.',
                    'errors' => [
                        'file' => ['The uploaded SVG file contains disallowed scripts or attributes.'],
                    ],
                ], 422);
            }
        }

        $type = $request->input('type', 'asset');
        $filename = "{$type}_".uniqid().".{$extension}";
        $path = $file->storeAs('branding', $filename, 'public');

        $url = asset('storage/'.$path);

        return response()->json([
            'url' => $url,
            'path' => $path,
        ]);
    }
}

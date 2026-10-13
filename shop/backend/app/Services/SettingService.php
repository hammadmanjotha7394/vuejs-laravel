<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingService
{
    public const CACHE_KEY = 'shop_settings_all';

    public const PUBLIC_CACHE_KEY = 'shop_settings_public';

    public const SECRET_KEYS = [
        'stripe_secret_key',
        'stripe_webhook_secret',
        'mail_password',
    ];

    public const SUPER_ADMIN_ONLY_KEYS = [
        'custom_head_scripts',
        'custom_body_scripts',
    ];

    public const GROUP_MAP = [
        // General
        'site_name' => 'general',
        'site_tagline' => 'general',
        'logo_url' => 'general',
        'favicon_url' => 'general',
        'brand_color' => 'general',
        'support_email' => 'general',
        'support_phone' => 'general',
        'store_address' => 'general',
        'currency_code' => 'general',
        'currency_symbol' => 'general',
        'timezone' => 'general',

        // Announcement
        'announcement_enabled' => 'announcement',
        'announcement_text' => 'announcement',
        'announcement_link' => 'announcement',
        'announcement_bg' => 'announcement',
        'announcement_text_color' => 'announcement',
        'announcement_dismissible' => 'announcement',
        'announcement_start_date' => 'announcement',
        'announcement_end_date' => 'announcement',

        // Social
        'social_instagram' => 'social',
        'social_facebook' => 'social',
        'social_tiktok' => 'social',
        'social_youtube' => 'social',
        'social_x' => 'social',
        'social_whatsapp' => 'social',

        // Header / Footer
        'footer_tagline' => 'footer',
        'footer_columns' => 'footer',
        'payment_icons_enabled' => 'footer',
        'footer_copyright' => 'footer',

        // Stripe
        'stripe_enabled' => 'stripe',
        'stripe_mode' => 'stripe',
        'stripe_currency' => 'stripe',
        'stripe_publishable_key' => 'stripe',
        'stripe_secret_key' => 'stripe',
        'stripe_webhook_secret' => 'stripe',

        // Mail (SMTP)
        'mail_host' => 'mail',
        'mail_port' => 'mail',
        'mail_encryption' => 'mail',
        'mail_username' => 'mail',
        'mail_password' => 'mail',
        'mail_from_name' => 'mail',
        'mail_from_address' => 'mail',
        'mail_reply_to' => 'mail',

        // Shipping & Tax
        'default_shipping_fee' => 'shipping',
        'free_shipping_threshold' => 'shipping',
        'tax_rate_percent' => 'shipping',
        'prices_include_tax' => 'shipping',

        // SEO
        'seo_title' => 'seo',
        'seo_description' => 'seo',
        'seo_social_image' => 'seo',
        'google_analytics_id' => 'seo',
        'meta_pixel_id' => 'seo',
        'custom_head_scripts' => 'seo',
        'custom_body_scripts' => 'seo',

        // Store
        'maintenance_mode' => 'store',
        'maintenance_message' => 'store',
        'guest_checkout' => 'store',
        'review_moderation' => 'store',
        'require_email_verification' => 'store',
        'low_stock_threshold' => 'store',
        'low_stock_email' => 'store',
        'promo_banners' => 'marketing',
        'trust_points' => 'marketing',
    ];

    /**
     * Retrieve all settings as decrypted key-value pairs (cached in file cache).
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $settings = Setting::all();
            $map = [];

            foreach ($settings as $setting) {
                $val = $setting->value;
                if ($setting->is_encrypted && ! empty($val)) {
                    try {
                        $val = Crypt::decryptString($val);
                    } catch (DecryptException) {
                        // Fallback if decryption fails
                    }
                }
                $map[$setting->key] = $val;
            }

            return $map;
        });
    }

    /**
     * Get a single setting by key from cache.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * Get a JSON-encoded setting parsed into array/object.
     */
    public static function getJson(string $key, mixed $default = []): mixed
    {
        $val = self::get($key);
        if (is_array($val)) {
            return $val;
        }
        if (empty($val)) {
            return $default;
        }

        try {
            $decoded = json_decode((string) $val, true);

            return is_array($decoded) ? $decoded : $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Clear all cached settings.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::PUBLIC_CACHE_KEY);
    }

    /**
     * Mask a secret value for safe display in administrative UI.
     * e.g., sk_test_••••••••1234 or ••••••••••••
     */
    public static function maskSecret(?string $value): string
    {
        if (empty($value)) {
            return '';
        }

        $length = strlen($value);
        if ($length <= 8) {
            return '••••••••';
        }

        if (preg_match('/^(sk_test_|sk_live_|whsec_)/', $value, $matches)) {
            $prefix = $matches[1];
            $last4 = substr($value, -4);

            return $prefix.'••••••••'.$last4;
        }

        return '••••••••'.substr($value, -4);
    }

    /**
     * Check if a value is already masked.
     */
    public static function isMaskedValue(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        return str_contains($value, '••••');
    }

    /**
     * Set multiple settings at once, handling encryption, masking protection, and activity logs.
     *
     * @param  array<string, mixed>  $data
     */
    public static function setMany(array $data, ?object $user = null, ?string $ip = null): void
    {
        $isSuperAdmin = $user && method_exists($user, 'hasRole') && $user->hasRole('super_admin');
        $activityProperties = [];

        foreach ($data as $key => $value) {
            // Check if key is restricted to super admin only
            if (in_array($key, self::SUPER_ADMIN_ONLY_KEYS, true) && ! $isSuperAdmin) {
                continue;
            }

            $group = self::GROUP_MAP[$key] ?? 'general';

            if (in_array($key, self::SECRET_KEYS, true)) {
                // Ignore if empty or masked value sent
                if (self::isMaskedValue($value) || $value === null || $value === '') {
                    continue;
                }

                $encryptedValue = Crypt::encryptString((string) $value);
                Setting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $encryptedValue,
                        'group' => $group,
                        'is_encrypted' => true,
                    ]
                );
                $activityProperties[$key] = '[REDACTED]';
            } else {
                $storedVal = is_array($value) ? json_encode($value) : ($value === null ? null : (string) $value);
                Setting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $storedVal,
                        'group' => $group,
                        'is_encrypted' => false,
                    ]
                );

                $activityProperties[$key] = is_string($storedVal) && strlen($storedVal) > 100
                    ? substr($storedVal, 0, 100).'...'
                    : $storedVal;
            }
        }

        self::clearCache();

        if (! empty($activityProperties)) {
            ActivityLog::create([
                'user_id' => $user?->id,
                'action' => 'settings.updated',
                'subject_type' => Setting::class,
                'subject_id' => null,
                'properties_json' => $activityProperties,
                'ip' => $ip,
            ]);
        }
    }

    /**
     * Get safe public settings for the storefront.
     */
    public static function getPublicSettings(): array
    {
        return Cache::rememberForever(self::PUBLIC_CACHE_KEY, function () {
            $stripeEnabled = (bool) self::get('stripe_enabled', false);

            return [
                'general' => [
                    'site_name' => self::get('site_name', 'Boutique'),
                    'site_tagline' => self::get('site_tagline', 'Thoughtfully crafted essentials'),
                    'logo_url' => self::get('logo_url'),
                    'favicon_url' => self::get('favicon_url'),
                    'brand_color' => self::get('brand_color', '#2F5D50'),
                    'support_email' => self::get('support_email', 'support@shop.test'),
                    'support_phone' => self::get('support_phone', ''),
                    'store_address' => self::get('store_address', ''),
                    'currency_code' => self::get('currency_code', 'USD'),
                    'currency_symbol' => self::get('currency_symbol', '$'),
                    'timezone' => self::get('timezone', 'UTC'),
                ],
                'announcement' => [
                    'enabled' => (bool) self::get('announcement_enabled', true),
                    'text' => self::get('announcement_text', ''),
                    'link' => self::get('announcement_link', ''),
                    'bg_color' => self::get('announcement_bg', '#2F5D50'),
                    'text_color' => self::get('announcement_text_color', '#FFFFFF'),
                    'dismissible' => (bool) self::get('announcement_dismissible', true),
                    'start_date' => self::get('announcement_start_date'),
                    'end_date' => self::get('announcement_end_date'),
                ],
                'social' => [
                    'instagram' => self::get('social_instagram'),
                    'facebook' => self::get('social_facebook'),
                    'tiktok' => self::get('social_tiktok'),
                    'youtube' => self::get('social_youtube'),
                    'x' => self::get('social_x'),
                    'whatsapp' => self::get('social_whatsapp'),
                ],
                'footer' => [
                    'tagline' => self::get('footer_tagline', ''),
                    'columns' => self::getJson('footer_columns', []),
                    'payment_icons_enabled' => (bool) self::get('payment_icons_enabled', true),
                    'copyright' => self::get('footer_copyright', '© 2026 Boutique Inc. All rights reserved.'),
                ],
                'stripe' => [
                    'enabled' => $stripeEnabled,
                    'publishable_key' => $stripeEnabled ? self::get('stripe_publishable_key', '') : null,
                    'mode' => self::get('stripe_mode', 'test'),
                    'currency' => self::get('stripe_currency', 'usd'),
                ],
                'shipping' => [
                    'free_shipping_threshold' => (float) self::get('free_shipping_threshold', 75.0),
                    'default_shipping_fee' => (float) self::get('default_shipping_fee', 8.0),
                    'tax_rate_percent' => (float) self::get('tax_rate_percent', 0.0),
                    'prices_include_tax' => (bool) self::get('prices_include_tax', false),
                ],
                'store' => [
                    'maintenance_mode' => (bool) self::get('maintenance_mode', false),
                    'maintenance_message' => self::get('maintenance_message', 'We are currently under maintenance.'),
                    'guest_checkout' => (bool) self::get('guest_checkout', true),
                ],
                'seo' => [
                    'title' => self::get('seo_title', 'Boutique'),
                    'description' => self::get('seo_description', ''),
                    'social_image' => self::get('seo_social_image'),
                ],
                'promo_banners' => self::getJson('promo_banners', []),
                'trust_points' => self::getJson('trust_points', []),
            ];
        });
    }

    /**
     * Get all settings with secret values masked for admin UI.
     */
    public static function getAdminSettings(): array
    {
        $all = self::all();
        $masked = [];

        foreach ($all as $key => $val) {
            if (in_array($key, self::SECRET_KEYS, true)) {
                $masked[$key] = self::maskSecret($val);
            } else {
                $masked[$key] = $val;
            }
        }

        return $masked;
    }

    /**
     * Validate and sanitize SVG file content to prevent XSS.
     */
    public static function sanitizeSvg(string $svgContent): bool
    {
        $dangerousPatterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/\bon\w+\s*=/i', // onload=, onclick=, onerror=, etc.
            '/javascript\s*:/i',
            '/data:\s*text\/html/i',
            '/<iframe\b/i',
            '/<embed\b/i',
            '/<object\b/i',
            '/xlink:href\s*=\s*["\']javascript:/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $svgContent)) {
                return false;
            }
        }

        return true;
    }
}

<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // 1. General
            ['key' => 'site_name', 'value' => 'Boutique', 'group' => 'general', 'is_encrypted' => false],
            ['key' => 'site_tagline', 'value' => 'Thoughtfully crafted essentials for living well', 'group' => 'general', 'is_encrypted' => false],
            ['key' => 'logo_url', 'value' => null, 'group' => 'general', 'is_encrypted' => false],
            ['key' => 'favicon_url', 'value' => null, 'group' => 'general', 'is_encrypted' => false],
            ['key' => 'brand_color', 'value' => '#2F5D50', 'group' => 'general', 'is_encrypted' => false],
            ['key' => 'support_email', 'value' => 'support@shop.test', 'group' => 'general', 'is_encrypted' => false],
            ['key' => 'support_phone', 'value' => '+1 (555) 012-3456', 'group' => 'general', 'is_encrypted' => false],
            ['key' => 'store_address', 'value' => '123 Artisan Way, Suite 400, Portland, OR 97201', 'group' => 'general', 'is_encrypted' => false],
            ['key' => 'currency_code', 'value' => 'USD', 'group' => 'general', 'is_encrypted' => false],
            ['key' => 'currency_symbol', 'value' => '$', 'group' => 'general', 'is_encrypted' => false],
            ['key' => 'timezone', 'value' => 'UTC', 'group' => 'general', 'is_encrypted' => false],

            // 2. Announcement bar
            ['key' => 'announcement_enabled', 'value' => '1', 'group' => 'announcement', 'is_encrypted' => false],
            ['key' => 'announcement_text', 'value' => 'Complimentary domestic shipping on orders over $75 · Easy 30-day returns', 'group' => 'announcement', 'is_encrypted' => false],
            ['key' => 'announcement_link', 'value' => '/shop', 'group' => 'announcement', 'is_encrypted' => false],
            ['key' => 'announcement_bg', 'value' => '#2F5D50', 'group' => 'announcement', 'is_encrypted' => false],
            ['key' => 'announcement_text_color', 'value' => '#FFFFFF', 'group' => 'announcement', 'is_encrypted' => false],
            ['key' => 'announcement_dismissible', 'value' => '1', 'group' => 'announcement', 'is_encrypted' => false],
            ['key' => 'announcement_start_date', 'value' => null, 'group' => 'announcement', 'is_encrypted' => false],
            ['key' => 'announcement_end_date', 'value' => null, 'group' => 'announcement', 'is_encrypted' => false],

            // 3. Social links
            ['key' => 'social_instagram', 'value' => 'https://instagram.com/boutique', 'group' => 'social', 'is_encrypted' => false],
            ['key' => 'social_facebook', 'value' => 'https://facebook.com/boutique', 'group' => 'social', 'is_encrypted' => false],
            ['key' => 'social_tiktok', 'value' => 'https://tiktok.com/@boutique', 'group' => 'social', 'is_encrypted' => false],
            ['key' => 'social_youtube', 'value' => 'https://youtube.com/@boutique', 'group' => 'social', 'is_encrypted' => false],
            ['key' => 'social_x', 'value' => 'https://x.com/boutique', 'group' => 'social', 'is_encrypted' => false],
            ['key' => 'social_whatsapp', 'value' => '+15550123456', 'group' => 'social', 'is_encrypted' => false],

            // 4. Header / Footer
            ['key' => 'footer_tagline', 'value' => 'Timeless essentials designed with calm simplicity and lasting durability.', 'group' => 'footer', 'is_encrypted' => false],
            ['key' => 'footer_columns', 'value' => json_encode([
                [
                    'title' => 'Shop',
                    'links' => [
                        ['label' => 'New Arrivals', 'url' => '/shop'],
                        ['label' => 'Best Sellers', 'url' => '/shop'],
                        ['label' => 'Apparel', 'url' => '/category/apparel'],
                        ['label' => 'Home & Living', 'url' => '/category/home-living'],
                    ],
                ],
                [
                    'title' => 'Support',
                    'links' => [
                        ['label' => 'Shipping & Delivery', 'url' => '/page/shipping'],
                        ['label' => 'Returns Policy', 'url' => '/page/returns'],
                        ['label' => 'Help & FAQs', 'url' => '/page/faq'],
                        ['label' => 'Contact Us', 'url' => '/contact'],
                    ],
                ],
                [
                    'title' => 'Company',
                    'links' => [
                        ['label' => 'Our Story', 'url' => '/page/about'],
                        ['label' => 'Sustainability', 'url' => '/page/about'],
                        ['label' => 'Privacy Policy', 'url' => '/page/privacy'],
                        ['label' => 'Terms of Service', 'url' => '/page/terms'],
                    ],
                ],
            ]), 'group' => 'footer', 'is_encrypted' => false],
            ['key' => 'payment_icons_enabled', 'value' => '1', 'group' => 'footer', 'is_encrypted' => false],
            ['key' => 'footer_copyright', 'value' => '© 2026 Boutique Inc. All rights reserved.', 'group' => 'footer', 'is_encrypted' => false],

            // 5. Stripe
            ['key' => 'stripe_enabled', 'value' => '0', 'group' => 'stripe', 'is_encrypted' => false],
            ['key' => 'stripe_mode', 'value' => 'test', 'group' => 'stripe', 'is_encrypted' => false],
            ['key' => 'stripe_currency', 'value' => 'usd', 'group' => 'stripe', 'is_encrypted' => false],
            ['key' => 'stripe_publishable_key', 'value' => 'pk_test_sample_pub_key_123', 'group' => 'stripe', 'is_encrypted' => false],
            ['key' => 'stripe_secret_key', 'value' => 'sk_test_sample_secret_key_123', 'group' => 'stripe', 'is_encrypted' => true],
            ['key' => 'stripe_webhook_secret', 'value' => 'whsec_sample_webhook_secret_123', 'group' => 'stripe', 'is_encrypted' => true],

            // 6. Email (Hostinger SMTP)
            ['key' => 'mail_host', 'value' => 'smtp.hostinger.com', 'group' => 'mail', 'is_encrypted' => false],
            ['key' => 'mail_port', 'value' => '465', 'group' => 'mail', 'is_encrypted' => false],
            ['key' => 'mail_encryption', 'value' => 'ssl', 'group' => 'mail', 'is_encrypted' => false],
            ['key' => 'mail_username', 'value' => 'support@shop.test', 'group' => 'mail', 'is_encrypted' => false],
            ['key' => 'mail_password', 'value' => 'sample_smtp_password', 'group' => 'mail', 'is_encrypted' => true],
            ['key' => 'mail_from_name', 'value' => 'Boutique Store', 'group' => 'mail', 'is_encrypted' => false],
            ['key' => 'mail_from_address', 'value' => 'support@shop.test', 'group' => 'mail', 'is_encrypted' => false],
            ['key' => 'mail_reply_to', 'value' => 'support@shop.test', 'group' => 'mail', 'is_encrypted' => false],

            // 7. Shipping and Tax
            ['key' => 'default_shipping_fee', 'value' => '8.00', 'group' => 'shipping', 'is_encrypted' => false],
            ['key' => 'free_shipping_threshold', 'value' => '75.00', 'group' => 'shipping', 'is_encrypted' => false],
            ['key' => 'tax_rate_percent', 'value' => '7.5', 'group' => 'shipping', 'is_encrypted' => false],
            ['key' => 'prices_include_tax', 'value' => '0', 'group' => 'shipping', 'is_encrypted' => false],

            // 8. SEO
            ['key' => 'seo_title', 'value' => 'Boutique - Calming Modern Essentials', 'group' => 'seo', 'is_encrypted' => false],
            ['key' => 'seo_description', 'value' => 'Curated collection of everyday apparel, ceramic homeware, leather goods and sustainable living accessories.', 'group' => 'seo', 'is_encrypted' => false],
            ['key' => 'seo_social_image', 'value' => null, 'group' => 'seo', 'is_encrypted' => false],
            ['key' => 'google_analytics_id', 'value' => null, 'group' => 'seo', 'is_encrypted' => false],
            ['key' => 'meta_pixel_id', 'value' => null, 'group' => 'seo', 'is_encrypted' => false],
            ['key' => 'custom_head_scripts', 'value' => null, 'group' => 'seo', 'is_encrypted' => false],
            ['key' => 'custom_body_scripts', 'value' => null, 'group' => 'seo', 'is_encrypted' => false],

            // 9. Store
            ['key' => 'maintenance_mode', 'value' => '0', 'group' => 'store', 'is_encrypted' => false],
            ['key' => 'maintenance_message', 'value' => 'We are currently performing scheduled maintenance. Please check back shortly.', 'group' => 'store', 'is_encrypted' => false],
            ['key' => 'guest_checkout', 'value' => '1', 'group' => 'store', 'is_encrypted' => false],
            ['key' => 'review_moderation', 'value' => '1', 'group' => 'store', 'is_encrypted' => false],
            ['key' => 'require_email_verification', 'value' => '0', 'group' => 'store', 'is_encrypted' => false],
            ['key' => 'low_stock_threshold', 'value' => '5', 'group' => 'store', 'is_encrypted' => false],
            ['key' => 'low_stock_email', 'value' => 'admin@shop.test', 'group' => 'store', 'is_encrypted' => false],
        ];

        foreach ($settings as $setting) {
            Setting::set($setting['key'], $setting['value'], $setting['group'], $setting['is_encrypted']);
        }
    }
}

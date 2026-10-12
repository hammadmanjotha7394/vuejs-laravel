<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\EmailTemplate;
use App\Models\HeroSlide;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Popup;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

class MarketingAndCmsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Hero Slides
        HeroSlide::create([
            'title' => 'Mindfully Crafted Goods',
            'subtitle' => 'Minimalist apparel and everyday homeware designed to outlast fleeting trends.',
            'button_text' => 'Explore Collection',
            'button_url' => '/shop',
            'image_desktop' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1600&q=80',
            'image_mobile' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=800&q=80',
            'text_position' => 'left',
            'overlay_opacity' => 0.25,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        HeroSlide::create([
            'title' => 'Pure Organic Linen',
            'subtitle' => 'Breathable, tactile textures tailored for effortless daily comfort.',
            'button_text' => 'Shop Apparel',
            'button_url' => '/category/apparel',
            'image_desktop' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=1600&q=80',
            'image_mobile' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=800&q=80',
            'text_position' => 'left',
            'overlay_opacity' => 0.25,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        HeroSlide::create([
            'title' => 'Tactile Ceramic Objects',
            'subtitle' => 'Wheel-thrown stoneware and artisanal lighting to ground your living sanctuary.',
            'button_text' => 'Discover Homeware',
            'button_url' => '/category/home-living',
            'image_desktop' => 'https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=1600&q=80',
            'image_mobile' => 'https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=800&q=80',
            'text_position' => 'center',
            'overlay_opacity' => 0.20,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // 2. Popups
        Popup::create([
            'name' => 'First Order Privilege',
            'type' => 'newsletter',
            'title' => 'Enjoy 10% off your first order',
            'body' => 'Subscribe to receive quiet reflections on sustainable craft, private preview invitations, and complimentary shipping vouchers.',
            'image' => 'https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=600&q=80',
            'button_text' => 'Subscribe',
            'button_url' => '#',
            'trigger' => 'delay',
            'trigger_value' => 6,
            'frequency' => 'once_per_session',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // 3. Menus & Mega Menu Items
        $mainMenu = Menu::create(['name' => 'Main Navigation', 'slug' => 'main', 'is_active' => true]);
        $footerMenu = Menu::create(['name' => 'Footer Navigation', 'slug' => 'footer', 'is_active' => true]);

        // Main Menu top items
        $apparelItem = MenuItem::create([
            'menu_id' => $mainMenu->id,
            'label' => 'Apparel',
            'type' => 'category',
            'url' => '/category/apparel',
            'badge_text' => 'New',
            'sort_order' => 1,
            'column_index' => 1,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => $apparelItem->id,
            'label' => 'Tops & Shirts',
            'type' => 'category',
            'url' => '/category/tops-shirts',
            'sort_order' => 1,
            'column_index' => 1,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => $apparelItem->id,
            'label' => 'Trousers & Pants',
            'type' => 'category',
            'url' => '/category/trousers-pants',
            'sort_order' => 2,
            'column_index' => 1,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $mainMenu->id,
            'label' => 'Home & Living',
            'type' => 'category',
            'url' => '/category/home-living',
            'sort_order' => 2,
            'column_index' => 2,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $mainMenu->id,
            'label' => 'Bags & Leather',
            'type' => 'category',
            'url' => '/category/bags-leather',
            'sort_order' => 3,
            'column_index' => 3,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $mainMenu->id,
            'label' => 'About',
            'type' => 'page',
            'url' => '/about',
            'sort_order' => 4,
            'column_index' => 4,
            'is_active' => true,
        ]);

        // Footer items
        MenuItem::create(['menu_id' => $footerMenu->id, 'label' => 'About Us', 'type' => 'page', 'url' => '/about', 'sort_order' => 1]);
        MenuItem::create(['menu_id' => $footerMenu->id, 'label' => 'FAQ', 'type' => 'page', 'url' => '/faq', 'sort_order' => 2]);
        MenuItem::create(['menu_id' => $footerMenu->id, 'label' => 'Shipping & Delivery', 'type' => 'page', 'url' => '/shipping', 'sort_order' => 3]);
        MenuItem::create(['menu_id' => $footerMenu->id, 'label' => 'Returns & Exchanges', 'type' => 'page', 'url' => '/returns', 'sort_order' => 4]);
        MenuItem::create(['menu_id' => $footerMenu->id, 'label' => 'Privacy Policy', 'type' => 'page', 'url' => '/privacy', 'sort_order' => 5]);
        MenuItem::create(['menu_id' => $footerMenu->id, 'label' => 'Terms of Service', 'type' => 'page', 'url' => '/terms', 'sort_order' => 6]);

        // 4. Default CMS Pages
        $pages = [
            [
                'title' => 'About Our Studio',
                'slug' => 'about',
                'content' => '<h2>Mindful Design for Daily Living</h2><p>Founded on principles of calm proportion, durable utility, and sustainable sourcing, Boutique collaborates directly with independent artisans across Europe and Japan. We prioritize timeless materials over seasonal cycles.</p>',
                'seo_title' => 'About Our Studio | Boutique',
                'seo_description' => 'Discover our philosophy of thoughtful craftsmanship, honest materials, and sustainable design.',
            ],
            [
                'title' => 'Frequently Asked Questions',
                'slug' => 'faq',
                'content' => '<h2>Common Questions</h2><p><strong>Where do you manufacture?</strong> Our ceramic, linen, and leather goods are made in small workshops in Portugal, Spain, and Japan.</p><p><strong>How long does shipping take?</strong> Domestic orders arrive within 2–4 business days with carbon-neutral transit.</p>',
                'seo_title' => 'FAQ | Boutique Customer Care',
                'seo_description' => 'Answers to questions regarding shipping, returns, sustainability, and product care.',
            ],
            [
                'title' => 'Shipping & Delivery',
                'slug' => 'shipping',
                'content' => '<h2>Domestic & International Shipping</h2><p>All items are carefully packaged in 100% recyclable, plastic-free boxes. Orders placed before 1 PM EST are dispatched the same business day.</p>',
                'seo_title' => 'Shipping & Delivery Information | Boutique',
                'seo_description' => 'Reliable, carbon-neutral shipping rates and estimated delivery timelines.',
            ],
            [
                'title' => 'Returns & Guarantee',
                'slug' => 'returns',
                'content' => '<h2>Hassle-Free 30-Day Returns</h2><p>If you are not thoroughly satisfied with your purchase, you may initiate a return within 30 days of receipt in original, unworn condition.</p>',
                'seo_title' => 'Returns & Guarantee Policy | Boutique',
                'seo_description' => 'Simple 30-day returns and store warranty details.',
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy',
                'content' => '<h2>Your Privacy Matters</h2><p>We respect your personal data and never sell or distribute your contact details to third parties. We strictly use cookies to enable secure sessions and optimize website speed.</p>',
                'seo_title' => 'Privacy Policy | Boutique',
                'seo_description' => 'Our commitment to data privacy, secure cookie handling, and encrypted payment processing.',
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms',
                'content' => '<h2>Terms & Conditions</h2><p>By using Boutique, you agree to fair use, honest interactions, and standard commercial conditions under governing consumer protections.</p>',
                'seo_title' => 'Terms of Service | Boutique',
                'seo_description' => 'Store terms of service, payment guarantees, and customer rights.',
            ],
        ];

        foreach ($pages as $p) {
            Page::create(array_merge($p, ['is_published' => true]));
        }

        // 5. Shipping Zones & Rates
        $domesticZone = ShippingZone::create([
            'name' => 'Domestic (United States)',
            'countries_json' => ['US'],
            'is_active' => true,
        ]);

        ShippingRate::create([
            'shipping_zone_id' => $domesticZone->id,
            'name' => 'Standard Ground (2-4 Days)',
            'rate' => 8.00,
            'free_above_subtotal' => 75.00,
            'is_active' => true,
        ]);

        ShippingRate::create([
            'shipping_zone_id' => $domesticZone->id,
            'name' => 'Express Priority (1-2 Days)',
            'rate' => 18.00,
            'free_above_subtotal' => null,
            'is_active' => true,
        ]);

        // 6. Coupons
        Coupon::create([
            'code' => 'WELCOME10',
            'type' => 'percent',
            'value' => 10.00,
            'min_subtotal' => 50.00,
            'max_uses' => 500,
            'used_count' => 12,
            'per_user_limit' => 1,
            'starts_at' => now()->subDays(10),
            'expires_at' => now()->addYear(),
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'SPRING15',
            'type' => 'fixed',
            'value' => 15.00,
            'min_subtotal' => 100.00,
            'max_uses' => 200,
            'used_count' => 8,
            'per_user_limit' => 1,
            'starts_at' => now()->subDays(5),
            'expires_at' => now()->addMonths(6),
            'is_active' => true,
        ]);

        // 7. Default Email Templates
        $emailTemplates = [
            [
                'key' => 'welcome',
                'subject' => 'Welcome to Boutique',
                'body_html' => '<div style="font-family:sans-serif;line-height:1.6;color:#111827;"><h2>Welcome, {{customer_name}}</h2><p>Thank you for joining our community of mindful living. Your account is ready.</p></div>',
            ],
            [
                'key' => 'order_confirmation',
                'subject' => 'Order Confirmation #{{order_number}}',
                'body_html' => '<div style="font-family:sans-serif;line-height:1.6;color:#111827;"><h2>Thank you for your order, {{customer_name}}!</h2><p>We are preparing your items with care. Your order number is <strong>#{{order_number}}</strong>.</p><p>Total: {{order_total}}</p></div>',
            ],
            [
                'key' => 'order_shipped',
                'subject' => 'Your order #{{order_number}} is on its way',
                'body_html' => '<div style="font-family:sans-serif;line-height:1.6;color:#111827;"><h2>Your package has shipped</h2><p>Track your shipment here: <a href="{{tracking_link}}">{{tracking_number}}</a></p></div>',
            ],
            [
                'key' => 'password_reset',
                'subject' => 'Reset Your Boutique Password',
                'body_html' => '<div style="font-family:sans-serif;line-height:1.6;color:#111827;"><h2>Password Reset Request</h2><p>Click below to securely reset your password:</p><p><a href="{{reset_url}}">Reset Password</a></p></div>',
            ],
            [
                'key' => 'new_order_admin',
                'subject' => 'New Order Alert #{{order_number}}',
                'body_html' => '<div style="font-family:sans-serif;line-height:1.6;color:#111827;"><h2>New Order Received</h2><p>Order #{{order_number}} from {{customer_email}} totaling {{order_total}}.</p></div>',
            ],
        ];

        foreach ($emailTemplates as $tpl) {
            EmailTemplate::create(array_merge($tpl, ['is_active' => true]));
        }
    }
}

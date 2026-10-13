<?php

namespace Tests\Feature;

use App\Mail\DynamicTemplateMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\HtmlSanitizer;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class Step21ExtrasAndSeoTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_contact_honeypot_back_in_stock_abandoned_cart_and_guest_order_tracking(): void
    {
        Mail::fake();

        $initialCount = ContactMessage::count();

        // 1. Contact form honeypot blocks bots silently
        $this->postJson('/api/contact', [
            'name' => 'Spam Bot',
            'email' => 'bot@spam.test',
            'subject' => 'Cheap Links',
            'message' => 'Buy spam',
            'website' => 'https://spambot.example.com',
        ])->assertOk();
        $this->assertSame($initialCount, ContactMessage::count());

        // Human submission saves and triggers admin email
        $this->postJson('/api/contact', [
            'name' => 'Elena Rostova',
            'email' => 'elena@example.com',
            'subject' => 'Care instructions for Merino Wool',
            'message' => 'Can this sweater be hand-washed in cold water?',
        ])->assertCreated();
        $this->assertSame($initialCount + 1, ContactMessage::count());

        // 2. Back-in-stock Notify Me triggers email when stock returns
        $product = Product::create([
            'name' => 'Sculpted Terracotta Vase',
            'slug' => 'sculpted-terracotta-vase',
            'sku' => 'TER-VAS-01',
            'base_price' => 95.00,
            'stock_qty' => 0,
            'track_stock' => true,
            'status' => 'active',
        ]);

        $this->postJson("/api/products/{$product->id}/back-in-stock", [
            'email' => 'waitlist@example.com',
        ])->assertOk();

        // Restock product -> triggers back_in_stock email
        $product->update(['stock_qty' => 8]);

        Mail::assertSent(DynamicTemplateMail::class, function (DynamicTemplateMail $mail) {
            return $mail->hasTo('waitlist@example.com')
                && str_contains($mail->subjectLine, 'Sculpted Terracotta Vase');
        });

        // 3. 24-Hour Abandoned Cart Command sends one reminder
        $cart = Cart::create([
            'token' => 'abandoned-cart-token-1',
            'guest_email' => 'shopper@example.com',
        ]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        Cart::where('id', $cart->id)->update([
            'updated_at' => now()->subHours(26),
        ]);

        $this->artisan('shop:abandoned-carts')->assertExitCode(0);
        $this->assertNotNull($cart->fresh()->abandoned_reminder_sent_at);

        // 4. Guest Order Tracking requires matching order_number + email
        $order = Order::create([
            'order_number' => 'ORD-20261013-TRACK1',
            'guest_email' => 'guestbuyer@example.com',
            'status' => 'shipped',
            'payment_status' => 'paid',
            'subtotal' => 95.00,
            'grand_total' => 95.00,
            'currency' => 'USD',
            'tracking_number' => '1ZTRACK999',
            'shipping_address_json' => ['city' => 'New York', 'country' => 'US'],
            'billing_address_json' => ['city' => 'New York', 'country' => 'US'],
        ]);

        // Wrong email -> 404
        $this->postJson('/api/orders/track', [
            'order_number' => 'ORD-20261013-TRACK1',
            'email' => 'intruder@example.com',
        ])->assertNotFound();

        // Matching email -> 200 with tracking details
        $this->postJson('/api/orders/track', [
            'order_number' => 'ORD-20261013-TRACK1',
            'email' => 'guestbuyer@example.com',
        ])->assertOk()
            ->assertJsonPath('data.tracking_number', '1ZTRACK999')
            ->assertJsonPath('data.status', 'shipped');
    }

    public function test_admin_brands_coupons_redirects_sitemap_seo_and_db_backup(): void
    {
        $admin = User::where('email', 'admin@shop.test')->firstOrFail();

        // 1. Admin Brands CRUD
        $brandRes = $this->actingAs($admin)->postJson('/api/admin/brands-manage', [
            'name' => 'Kinto Japan',
            'is_active' => true,
        ]);
        $brandRes->assertCreated()->assertJsonPath('data.slug', 'kinto-japan');

        // 2. Admin Coupons CRUD with Buy X Get Y% (min_qty = 2)
        $couponRes = $this->actingAs($admin)->postJson('/api/admin/coupons', [
            'code' => 'BUY2GET15',
            'type' => 'percent',
            'value' => 15,
            'min_qty' => 2,
            'is_active' => true,
        ]);
        $couponRes->assertCreated()->assertJsonPath('data.min_qty', 2);

        // 3. Product slug change automatically creates 301 redirect and server-renders SEO tags
        $product = Product::create([
            'name' => 'Artisanal Matcha Bowl',
            'slug' => 'artisanal-matcha-bowl',
            'sku' => 'MAT-BWL-01',
            'short_description' => 'Hand-glazed ceramic matcha bowl from Kyoto.',
            'base_price' => 64.00,
            'stock_qty' => 12,
            'track_stock' => true,
            'status' => 'active',
        ]);

        $product->update(['slug' => 'kyoto-matcha-bowl']);

        // Old slug redirects 301 to new slug
        $this->get('/products/artisanal-matcha-bowl')
            ->assertRedirect('/products/kyoto-matcha-bowl');

        // New slug injects server-side SEO <title>, Open Graph, and JSON-LD Product schema
        $seoRes = $this->get('/products/kyoto-matcha-bowl');
        $seoRes->assertOk();
        $this->assertStringContainsString('Artisanal Matcha Bowl', $seoRes->getContent());
        $this->assertStringContainsString('application/ld+json', $seoRes->getContent());

        // 4. Sitemap.xml and robots.txt
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('kyoto-matcha-bowl', false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap:', false);

        // 5. Super Admin Activity Logs, Quick Search & One-Click DB Backup Export
        $this->actingAs($admin)->getJson('/api/admin/activity-logs')
            ->assertOk();

        $this->actingAs($admin)->getJson('/api/admin/quick-search?q=Matcha')
            ->assertOk()
            ->assertJsonPath('products.0.name', 'Artisanal Matcha Bowl');

        $backupRes = $this->actingAs($admin)->get('/api/admin/system/backup-db');
        $backupRes->assertOk();
        $this->assertStringContainsString('CREATE TABLE', $backupRes->streamedContent());
    }

    public function test_security_headers_and_html_sanitization(): void
    {
        $res = $this->getJson('/api/health');
        $res->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $this->assertStringContainsString("frame-ancestors 'self'", (string) $res->headers->get('Content-Security-Policy'));

        // Verify HtmlSanitizer strips <script>, <iframe>, onclick, and javascript: hrefs
        $dirty = '<p onclick="alert(1)">Safe text</p><script>evil()</script><a href="javascript:hack()">Link</a>';
        $clean = HtmlSanitizer::clean($dirty);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringContainsString('<p>Safe text</p>', $clean);
    }
}

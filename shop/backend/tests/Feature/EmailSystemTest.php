<?php

namespace Tests\Feature;

use App\Mail\DynamicTemplateMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\EmailService;
use App\Services\SettingService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_admin_can_edit_email_templates_and_order_completion_uses_updated_templates(): void
    {
        Mail::fake();

        $admin = User::where('email', 'admin@shop.test')->firstOrFail();

        // 1. Configure Hostinger SMTP runtime settings
        SettingService::setMany([
            'site_name' => 'Atelier Maison',
            'support_email' => 'orders@ateliermaison.com',
            'mail_host' => 'smtp.hostinger.com',
            'mail_port' => 465,
            'mail_encryption' => 'ssl',
            'mail_username' => 'orders@ateliermaison.com',
            'mail_password' => 'HostingerSmtpPass123!',
            'mail_from_name' => 'Atelier Maison Concierge',
            'mail_from_address' => 'orders@ateliermaison.com',
        ], $admin, '127.0.0.1');

        EmailService::applyRuntimeSmtpConfig();
        $this->assertSame('smtp.hostinger.com', config('mail.mailers.smtp.host'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('orders@ateliermaison.com', config('mail.from.address'));

        // 2. Fetch templates via Admin API (auto-seeds all 16 templates)
        $listRes = $this->actingAs($admin)->getJson('/api/admin/email-templates');
        $listRes->assertOk();
        $this->assertGreaterThanOrEqual(16, count($listRes->json('data')));

        $orderConfirmedTpl = EmailTemplate::where('key', 'order_confirmed')->firstOrFail();
        $adminNewOrderTpl = EmailTemplate::where('key', 'admin_new_order')->firstOrFail();

        // 3. Edit the order_confirmed template in Admin
        $this->actingAs($admin)->putJson("/api/admin/email-templates/{$orderConfirmedTpl->id}", [
            'subject' => 'Bespoke Confirmation for {{customer_name}} — #{{order_number}}',
            'body_html' => '<p>Custom VIP thank you message for {{customer_name}} spending {{order_total}}.</p>{{items_table}}',
            'is_active' => true,
        ])->assertOk();

        // 4. Edit the admin_new_order template in Admin
        $this->actingAs($admin)->putJson("/api/admin/email-templates/{$adminNewOrderTpl->id}", [
            'subject' => '[URGENT DISPATCH] Order {{order_number}} ({{order_total}})',
            'body_html' => '<p>Staff alert: new order from {{customer_email}}.</p>',
            'is_active' => true,
        ])->assertOk();

        // 5. Preview the edited template
        $previewRes = $this->actingAs($admin)->postJson("/api/admin/email-templates/{$orderConfirmedTpl->id}/preview", [
            'subject' => $orderConfirmedTpl->fresh()->subject,
            'body_html' => $orderConfirmedTpl->fresh()->body_html,
        ]);
        $previewRes->assertOk()
            ->assertJsonPath('data.subject', 'Bespoke Confirmation for Clara Vance — #ORD-20261013-89AB12');
        $this->assertStringContainsString('Custom VIP thank you message for Clara Vance', $previewRes->json('data.html'));

        // 6. Place a test order and complete checkout
        $product = Product::create([
            'name' => 'Cashmere Travel Wrap',
            'slug' => 'cashmere-travel-wrap',
            'sku' => 'CSH-WRP-01',
            'base_price' => 240.00,
            'stock_qty' => 10,
            'track_stock' => true,
            'is_active' => true,
        ]);

        $cart = Cart::create(['token' => 'test-cart-token-step20']);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $checkoutResult = CheckoutService::createOrderAndSession($cart, [
            'email' => 'buyer@example.com',
            'shipping_address' => [
                'first_name' => 'Julian',
                'last_name' => 'Mercer',
                'line1' => '88 Crosby Street',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10012',
                'country' => 'US',
            ],
        ]);

        $orderNumber = $checkoutResult['order_number'];

        CheckoutService::handleCheckoutSessionCompleted([
            'id' => $checkoutResult['session_id'],
            'payment_intent' => 'pi_test_step20_123',
            'metadata' => [
                'order_id' => $checkoutResult['order_id'],
                'cart_id' => $cart->id,
            ],
        ]);

        // 7. Verify both customer confirmation and admin alert were sent with the EDITED template subjects & HTML
        Mail::assertSent(DynamicTemplateMail::class, function (DynamicTemplateMail $mail) use ($orderNumber) {
            return $mail->hasTo('buyer@example.com')
                && $mail->subjectLine === "Bespoke Confirmation for Julian Mercer — #{$orderNumber}"
                && str_contains($mail->renderedHtml, 'Custom VIP thank you message for Julian Mercer')
                && str_contains($mail->renderedHtml, 'Cashmere Travel Wrap');
        });

        Mail::assertSent(DynamicTemplateMail::class, function (DynamicTemplateMail $mail) use ($orderNumber) {
            return $mail->hasTo('orders@ateliermaison.com')
                && str_contains($mail->subjectLine, "[URGENT DISPATCH] Order {$orderNumber}");
        });

        // 8. Verify EmailLog entries and resend capability
        $customerLog = EmailLog::where('to', 'buyer@example.com')
            ->where('template_key', 'order_confirmed')
            ->first();
        $this->assertNotNull($customerLog);
        $this->assertSame('sent', $customerLog->status);

        $resendRes = $this->actingAs($admin)->postJson("/api/admin/email-logs/{$customerLog->id}/resend");
        $resendRes->assertOk()->assertJsonPath('success', true);
    }
}

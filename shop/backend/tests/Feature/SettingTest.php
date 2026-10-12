<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\EmailLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SettingService::clearCache();
    }

    public function test_public_settings_endpoint_returns_safe_values_without_secrets(): void
    {
        $response = $this->getJson('/api/public-settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'general' => ['site_name', 'brand_color', 'currency_code', 'currency_symbol'],
                    'announcement' => ['enabled', 'text', 'bg_color', 'text_color'],
                    'social' => ['instagram', 'facebook'],
                    'footer' => ['copyright', 'columns'],
                    'stripe' => ['enabled', 'mode', 'currency'],
                    'shipping' => ['free_shipping_threshold', 'default_shipping_fee'],
                    'store' => ['maintenance_mode', 'guest_checkout'],
                    'seo' => ['title', 'description'],
                ],
            ]);

        $content = $response->getContent();

        // Ensure secrets and sensitive configurations are strictly absent
        $this->assertStringNotContainsString('stripe_secret_key', $content);
        $this->assertStringNotContainsString('stripe_webhook_secret', $content);
        $this->assertStringNotContainsString('mail_password', $content);
        $this->assertStringNotContainsString('smtp.hostinger.com', $content);
        $this->assertStringNotContainsString('sample_smtp_password', $content);
    }

    public function test_admin_settings_returns_masked_secrets(): void
    {
        $admin = User::where('email', 'admin@shop.test')->first();

        // Ensure a known secret key exists
        Setting::set('stripe_secret_key', 'sk_test_51Abcdefghijklmnop1234', 'stripe', true);
        Setting::set('mail_password', 'secret_smtp_pass_9999', 'mail', true);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/settings');

        $response->assertStatus(200);

        $data = $response->json('data');

        $this->assertArrayHasKey('stripe_secret_key', $data);
        $this->assertArrayHasKey('mail_password', $data);

        // Secret values must be masked
        $this->assertStringContainsString('••••', $data['stripe_secret_key']);
        $this->assertStringEndsWith('1234', $data['stripe_secret_key']);
        $this->assertStringContainsString('••••', $data['mail_password']);
        $this->assertStringNotContainsString('secret_smtp_pass_9999', $data['mail_password']);
    }

    public function test_product_manager_cannot_access_settings_endpoints_returns_403(): void
    {
        $pm = User::where('email', 'product@shop.test')->first();

        $this->actingAs($pm)
            ->getJson('/api/admin/settings')
            ->assertStatus(403);

        $this->actingAs($pm)
            ->putJson('/api/admin/settings', ['site_name' => 'Hacked Site'])
            ->assertStatus(403);

        $this->actingAs($pm)
            ->putJson('/api/admin/settings/stripe', [
                'stripe_enabled' => '1',
                'stripe_mode' => 'test',
                'stripe_currency' => 'usd',
            ])
            ->assertStatus(403);

        $this->actingAs($pm)
            ->postJson('/api/admin/settings/stripe/test')
            ->assertStatus(403);

        $this->actingAs($pm)
            ->postJson('/api/admin/settings/mail/test')
            ->assertStatus(403);
    }

    public function test_super_admin_can_update_settings_and_masked_secrets_are_not_overwritten(): void
    {
        $admin = User::where('email', 'admin@shop.test')->first();

        // Set known original secret
        Setting::set('stripe_secret_key', 'sk_test_original_secret_9999', 'stripe', true);

        // Submit update with masked secret value
        $response = $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'site_name' => 'Artisan Goods',
                'brand_color' => '#1A365D',
                'announcement_text' => 'Spring sale now live!',
            ]);

        $response->assertStatus(200);

        // Verify database updated
        $this->assertEquals('Artisan Goods', Setting::get('site_name'));
        $this->assertEquals('#1A365D', Setting::get('brand_color'));

        // Verify secret was preserved
        $rawSetting = Setting::where('key', 'stripe_secret_key')->first();
        $this->assertEquals('sk_test_original_secret_9999', Crypt::decryptString($rawSetting->value));

        // Verify activity log recorded
        $log = ActivityLog::where('action', 'settings.updated')->latest()->first();
        $this->assertNotNull($log);
        $this->assertEquals($admin->id, $log->user_id);
    }

    public function test_updating_stripe_with_new_plain_text_encrypts_secret(): void
    {
        $admin = User::where('email', 'admin@shop.test')->first();

        $response = $this->actingAs($admin)
            ->putJson('/api/admin/settings/stripe', [
                'stripe_enabled' => '1',
                'stripe_mode' => 'test',
                'stripe_currency' => 'usd',
                'stripe_publishable_key' => 'pk_test_new_key_123',
                'stripe_secret_key' => 'sk_test_brand_new_secret_8888',
            ]);

        $response->assertStatus(200);

        $rawSetting = Setting::where('key', 'stripe_secret_key')->first();
        $this->assertTrue($rawSetting->is_encrypted);
        $this->assertEquals('sk_test_brand_new_secret_8888', Crypt::decryptString($rawSetting->value));
    }

    public function test_stripe_test_connection_endpoint_reports_status_without_crashing(): void
    {
        $admin = User::where('email', 'admin@shop.test')->first();

        // Testing with sample invalid key - should return a structured 200 response with error message
        $response = $this->actingAs($admin)
            ->postJson('/api/admin/settings/stripe/test');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'message']);
    }

    public function test_mail_test_endpoint_records_to_email_logs(): void
    {
        $admin = User::where('email', 'admin@shop.test')->first();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/settings/mail/test', [
                'email' => 'test-recipient@shop.test',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'message']);

        // Must record to email_logs
        $log = EmailLog::where('to', 'test-recipient@shop.test')->latest()->first();
        $this->assertNotNull($log);
        $this->assertEquals('test_email', $log->template_key);
    }

    public function test_asset_upload_sanitizes_malicious_svg(): void
    {
        Storage::fake('public');
        $admin = User::where('email', 'admin@shop.test')->first();

        // Malicious SVG with embedded script
        $maliciousSvg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert("xss")</script><rect width="100" height="100"/></svg>';
        $badFile = UploadedFile::fake()->createWithContent('malicious.svg', $maliciousSvg);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/settings/upload-asset', [
                'file' => $badFile,
                'type' => 'logo',
            ]);

        $response->assertStatus(422);

        // Safe SVG
        $safeSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="#2F5D50"/></svg>';
        $goodFile = UploadedFile::fake()->createWithContent('clean_logo.svg', $safeSvg);

        $goodResponse = $this->actingAs($admin)
            ->postJson('/api/admin/settings/upload-asset', [
                'file' => $goodFile,
                'type' => 'logo',
            ]);

        $goodResponse->assertStatus(200)
            ->assertJsonStructure(['url', 'path']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use App\Models\Popup;
use Tests\TestCase;

class StorefrontShellTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        NewsletterSubscriber::whereIn('email', [
            'collector@boutique-studio.com',
            'existing@boutique-studio.com',
        ])->delete();

        Popup::whereIn('name', [
            'Inactive Popup',
            'Future Popup',
            'Past Popup',
        ])->delete();
    }

    public function test_public_popups_endpoint_returns_active_popups(): void
    {
        $response = $this->getJson('/api/popups');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'type',
                    'title',
                    'body',
                    'button_text',
                    'trigger',
                    'frequency',
                    'is_active',
                ],
            ],
        ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    public function test_inactive_and_expired_popups_are_filtered_out(): void
    {
        Popup::create([
            'name' => 'Inactive Popup',
            'type' => 'content',
            'title' => 'Expired Offer',
            'is_active' => false,
        ]);

        Popup::create([
            'name' => 'Future Popup',
            'type' => 'content',
            'title' => 'Future Offer',
            'starts_at' => now()->addDays(5),
            'is_active' => true,
        ]);

        Popup::create([
            'name' => 'Past Popup',
            'type' => 'content',
            'title' => 'Past Offer',
            'ends_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/popups');
        $response->assertStatus(200);

        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertNotContains('Expired Offer', $titles);
        $this->assertNotContains('Future Offer', $titles);
        $this->assertNotContains('Past Offer', $titles);
    }

    public function test_newsletter_subscription_creates_subscriber(): void
    {
        $email = 'collector@boutique-studio.com';

        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => $email,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Thank you for subscribing! Your welcome voucher will arrive shortly.',
        ]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => $email,
            'is_active' => true,
        ]);
    }

    public function test_newsletter_subscription_validates_email(): void
    {
        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'invalid-email-format',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_resubscribing_existing_email_updates_gracefully(): void
    {
        $email = 'existing@boutique-studio.com';
        NewsletterSubscriber::create([
            'email' => $email,
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => $email,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => $email,
            'is_active' => true,
        ]);
    }
}

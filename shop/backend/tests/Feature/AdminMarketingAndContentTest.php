<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\Page;
use App\Models\Popup;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminMarketingAndContentTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $productManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->superAdmin = User::factory()->create(['is_active' => true]);
        $this->superAdmin->assignRole('super_admin');

        $this->productManager = User::factory()->create(['is_active' => true]);
        $this->productManager->assignRole('product_manager');
    }

    public function test_hero_slides_crud_and_reorder_immediately_reflect_on_storefront(): void
    {
        $createRes = $this->actingAs($this->superAdmin)
            ->postJson('/api/admin/hero-slides', [
                'title' => 'Autumn Linen Collection',
                'subtitle' => 'Breathable organic flax crafted in Portugal',
                'button_text' => 'Explore Collection',
                'button_url' => '/category/linen',
                'image_desktop' => 'https://example.com/hero-desktop.jpg',
                'image_mobile' => 'https://example.com/hero-mobile.jpg',
                'text_position' => 'center',
                'overlay_opacity' => 0.45,
                'sort_order' => 1,
                'is_active' => true,
            ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('data.title', 'Autumn Linen Collection');

        $slideId = $createRes->json('data.id');

        // Verify storefront GET /api/hero-slides immediately includes the new slide (cache cleared)
        $publicRes = $this->getJson('/api/hero-slides');
        $publicRes->assertStatus(200);
        $titles = collect($publicRes->json('data'))->pluck('title')->all();
        $this->assertContains('Autumn Linen Collection', $titles);

        // Create second slide and test reorder
        $secondSlide = HeroSlide::create([
            'title' => 'Winter Ceramic Studio',
            'image_desktop' => 'https://example.com/hero-2.jpg',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/admin/hero-slides/reorder', [
                'ids' => [$secondSlide->id, $slideId],
            ])
            ->assertStatus(200);

        $this->assertEquals(1, $secondSlide->fresh()->sort_order);
        $this->assertEquals(2, HeroSlide::find($slideId)->sort_order);
    }

    public function test_popups_crud_and_view_click_signup_tracking(): void
    {
        $res = $this->actingAs($this->superAdmin)
            ->postJson('/api/admin/popups', [
                'name' => 'Welcome 10% Voucher',
                'type' => 'newsletter',
                'title' => 'Join Our Studio Journal',
                'body' => 'Receive 10% off your first order.',
                'button_text' => 'Subscribe',
                'trigger' => 'delay',
                'trigger_value' => 5,
                'show_on_pages_json' => ['all'],
                'frequency' => 'once_per_session',
                'is_active' => true,
            ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.name', 'Welcome 10% Voucher');

        $popupId = $res->json('data.id');

        // Track view and click
        $this->postJson("/api/popups/{$popupId}/track", ['action' => 'view'])
            ->assertStatus(200)
            ->assertJsonPath('data.views_count', 1);

        $this->postJson("/api/popups/{$popupId}/track", ['action' => 'click'])
            ->assertStatus(200)
            ->assertJsonPath('data.clicks_count', 1);

        // Subscribe via newsletter with popup_id -> increments signups_count
        $this->postJson('/api/newsletter/subscribe', [
            'email' => 'popup-subscriber@example.com',
            'popup_id' => $popupId,
        ])->assertStatus(200);

        $popup = Popup::findOrFail($popupId);
        $this->assertEquals(1, $popup->views_count);
        $this->assertEquals(1, $popup->clicks_count);
        $this->assertEquals(1, $popup->signups_count);
    }

    public function test_home_banners_and_trust_points_reflect_in_public_settings(): void
    {
        $banners = [
            [
                'tag' => 'New Season',
                'title' => 'Raw Ceramic Tableware',
                'description' => 'Hand-thrown stoneware plates and bowls.',
                'button_text' => 'Shop Ceramics',
                'button_url' => '/category/ceramics',
                'image' => 'https://example.com/banner1.jpg',
            ],
        ];

        $trustPoints = [
            [
                'icon' => 'Truck',
                'title' => 'Free Express Delivery',
                'desc' => 'Complimentary shipping over $75.',
            ],
            [
                'icon' => 'RotateCcw',
                'title' => '30-Day Returns',
                'desc' => 'Hassle-free returns and exchanges.',
            ],
        ];

        $this->actingAs($this->superAdmin)
            ->putJson('/api/admin/marketing/home-blocks', [
                'promo_banners' => $banners,
                'trust_points' => $trustPoints,
            ])
            ->assertStatus(200);

        $publicSettings = $this->getJson('/api/public-settings');
        $publicSettings->assertStatus(200)
            ->assertJsonPath('data.promo_banners.0.title', 'Raw Ceramic Tableware')
            ->assertJsonPath('data.trust_points.0.title', 'Free Express Delivery');
    }

    public function test_cms_pages_crud_sanitizes_html_and_serves_public_page(): void
    {
        $createRes = $this->actingAs($this->superAdmin)
            ->postJson('/api/admin/pages', [
                'title' => 'Our Sustainability Pledge',
                'slug' => 'sustainability-pledge',
                'content_html' => '<h2>Zero Plastic Packaging</h2><p>Every parcel is 100% recyclable.</p><script>alert(1)</script>',
                'seo_title' => 'Sustainability Pledge — Atelier',
                'seo_description' => 'Our commitment to organic materials and carbon-neutral shipping.',
                'is_published' => true,
            ]);

        $createRes->assertStatus(201);
        $pageId = $createRes->json('data.id');

        // Verify script tag was stripped by sanitizer
        $page = Page::findOrFail($pageId);
        $this->assertStringNotContainsString('<script>', $page->content_html);
        $this->assertStringContainsString('<h2>Zero Plastic Packaging</h2>', $page->content_html);

        // Verify storefront GET /api/pages/sustainability-pledge returns the page
        $this->getJson('/api/pages/sustainability-pledge')
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Our Sustainability Pledge')
            ->assertJsonPath('data.seo_title', 'Sustainability Pledge — Atelier');
    }

    public function test_product_manager_can_edit_hero_and_popups_only_if_granted_permission(): void
    {
        // Product manager has 'hero.manage' by default in RoleAndPermissionSeeder
        $this->actingAs($this->productManager)
            ->getJson('/api/admin/hero-slides')
            ->assertStatus(200);

        // Product manager does NOT have 'popups.manage' by default -> gets 403
        $this->actingAs($this->productManager)
            ->getJson('/api/admin/popups')
            ->assertStatus(403);

        // Grant 'popups.manage' to productManager and verify access is now allowed
        $permission = Permission::where('name', 'popups.manage')->firstOrFail();
        $this->productManager->givePermissionTo($permission);

        $this->actingAs($this->productManager->fresh())
            ->getJson('/api/admin/popups')
            ->assertStatus(200);
    }
}

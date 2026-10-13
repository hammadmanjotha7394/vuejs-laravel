<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Services\SettingService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ReviewsSystemTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $customer;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->superAdmin = User::factory()->create(['is_active' => true]);
        $this->superAdmin->assignRole('super_admin');

        $this->customer = User::factory()->create(['is_active' => true, 'name' => 'Elena Rostova']);
        $this->customer->assignRole('customer');

        $this->product = Product::create([
            'name' => 'Washed Linen Duvet Set',
            'slug' => 'washed-linen-duvet-set',
            'sku' => 'DUV-LIN-01',
            'base_price' => 240.00,
            'status' => 'active',
            'track_stock' => true,
            'stock_qty' => 15,
        ]);
    }

    public function test_admin_created_review_is_indistinguishable_from_customer_review_in_public_api(): void
    {
        SettingService::setMany(['review_moderation' => false]);

        // 1. Customer submits a review with optional photo
        $custRes = $this->actingAs($this->customer)
            ->postJson("/api/products/{$this->product->id}/reviews", [
                'rating' => 5,
                'title' => 'Softest linen we have owned',
                'body' => 'Gets even more comfortable after every wash.',
                'photos' => ['https://example.com/review-photo-1.jpg'],
            ]);

        $custRes->assertStatus(201);

        // 2. Admin creates a custom review with custom reviewer name, avatar initials, and past review_date
        $adminRes = $this->actingAs($this->superAdmin)
            ->postJson('/api/admin/reviews', [
                'product_ids' => [$this->product->id],
                'author_name' => 'Henrik Lindqvist',
                'author_avatar' => 'HL',
                'rating' => 4,
                'title' => 'Heirloom quality stitching',
                'body' => 'Beautiful natural oat tone and breathable weave.',
                'review_date' => '2026-08-14',
                'is_approved' => true,
            ]);

        $adminRes->assertStatus(201)
            ->assertJsonPath('data.is_admin_created', true)
            ->assertJsonPath('data.created_by_admin_id', $this->superAdmin->id);

        // 3. Public product endpoint returns both reviews with identical structure and NO admin-identifying fields
        $publicRes = $this->getJson("/api/products/{$this->product->slug}");
        $publicRes->assertStatus(200);

        $publicReviews = $publicRes->json('data.product.reviews');
        $this->assertCount(2, $publicReviews);

        foreach ($publicReviews as $rev) {
            $this->assertArrayNotHasKey('is_admin_created', $rev);
            $this->assertArrayNotHasKey('created_by_admin_id', $rev);
            $this->assertArrayNotHasKey('user_id', $rev);
            $this->assertArrayHasKey('author_name', $rev);
            $this->assertArrayHasKey('rating', $rev);
            $this->assertArrayHasKey('title', $rev);
            $this->assertArrayHasKey('body', $rev);
            $this->assertArrayHasKey('review_date', $rev);
        }

        // Verify bothreviews have identical keys
        $this->assertEquals(array_keys($publicReviews[0]), array_keys($publicReviews[1]));

        // Verify rating summary was recalculated and cached (5 + 4 = 9 / 2 = 4.5)
        $this->assertEquals(4.5, $publicRes->json('data.product.rating_summary.average'));
        $this->assertEquals(2, $publicRes->json('data.product.rating_summary.count'));

        // 4. Admin endpoint CAN see the hidden flag
        $adminListRes = $this->actingAs($this->superAdmin)
            ->getJson("/api/admin/reviews?product_id={$this->product->id}");

        $adminListRes->assertStatus(200);
        $adminItems = collect($adminListRes->json('data'));
        $adminCreatedItem = $adminItems->firstWhere('author_name', 'Henrik Lindqvist');
        $customerItem = $adminItems->firstWhere('author_name', 'Elena Rostova');

        $this->assertTrue($adminCreatedItem['is_admin_created']);
        $this->assertEquals($this->superAdmin->id, $adminCreatedItem['created_by_admin_id']);
        $this->assertFalse($customerItem['is_admin_created']);
    }

    public function test_moderation_reply_and_csv_import_work_and_recalculate_ratings(): void
    {
        SettingService::setMany(['review_moderation' => true]);

        // Customer review when moderation is ON -> pending (is_approved = false)
        $res = $this->postJson("/api/products/{$this->product->id}/reviews", [
            'author_name' => 'Pending Reviewer',
            'rating' => 3,
            'title' => 'Good but took a week',
            'body' => 'Product is lovely, courier was slightly delayed.',
        ]);
        $res->assertStatus(201)
            ->assertJsonPath('is_approved', false);

        $review = Review::where('product_id', $this->product->id)->firstOrFail();
        $this->assertFalse($review->is_approved);

        // Public product page should have 0 approved reviews
        $this->getJson("/api/products/{$this->product->slug}")
            ->assertJsonPath('data.product.rating_summary.count', 0);

        // Admin approves the review and adds a public store reply
        $this->actingAs($this->superAdmin)
            ->patchJson("/api/admin/reviews/{$review->id}/status", ['is_approved' => true])
            ->assertStatus(200);

        $this->actingAs($this->superAdmin)
            ->postJson("/api/admin/reviews/{$review->id}/reply", [
                'admin_reply' => 'Thank you for your feedback! We have upgraded our studio courier service.',
            ])
            ->assertStatus(200);

        // Public product page now shows 1 review with the store reply
        $this->getJson("/api/products/{$this->product->slug}")
            ->assertJsonPath('data.product.rating_summary.count', 1)
            ->assertJsonPath('data.product.reviews.0.admin_reply', 'Thank you for your feedback! We have upgraded our studio courier service.');

        // Bulk import CSV of 2 5-star reviews
        $csvContent = "product_slug,author_name,author_avatar,rating,title,body,review_date,is_approved\n"
            ."{$this->product->slug},Astrid Holm,AH,5,Exceptional linen,Love the texture,2026-09-01,true\n"
            ."{$this->product->slug},Soren Kierk,SK,5,Worth every penny,Will buy again,2026-09-10,true\n";

        $csvFile = UploadedFile::fake()->createWithContent('reviews.csv', $csvContent);

        $this->actingAs($this->superAdmin)
            ->postJson('/api/admin/reviews/import-csv', ['file' => $csvFile])
            ->assertStatus(200)
            ->assertJsonPath('imported_count', 2);

        // Total approved reviews = 3 (ratings: 3, 5, 5 -> avg 4.3)
        $this->getJson("/api/products/{$this->product->slug}")
            ->assertJsonPath('data.product.rating_summary.count', 3)
            ->assertJsonPath('data.product.rating_summary.average', 4.3);
    }
}

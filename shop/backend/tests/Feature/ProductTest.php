<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_public_products_list_returns_card_payload_under_200ms(): void
    {
        // Warm up framework request cycle and database connection
        $this->getJson('/api/health');
        $this->getJson('/api/products?per_page=1');

        $startTime = microtime(true);

        $response = $this->getJson('/api/products?per_page=12');

        $durationMs = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                        'is_on_sale',
                        'sale_percentage',
                        'in_stock',
                        'primary_image',
                    ],
                ],
                'current_page',
                'total',
            ]);

        // Verify sub-200ms response time
        $this->assertLessThan(200, $durationMs, "Product listing took {$durationMs}ms, exceeding 200ms limit.");
    }

    public function test_public_product_details_endpoint_returns_complete_hydrated_structure(): void
    {
        $product = Product::whereIn('status', ['active', 'published'])->first();
        $this->assertNotNull($product, 'Seeded products should exist');

        $response = $this->getJson("/api/products/{$product->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'product' => [
                        'id',
                        'name',
                        'slug',
                        'base_price',
                        'description',
                        'images',
                        'variants',
                        'sections',
                        'specs',
                        'rating_summary',
                    ],
                    'related_products',
                ],
            ]);
    }

    public function test_search_suggestions_returns_maximum_six_items_fast(): void
    {
        $startTime = microtime(true);

        $response = $this->getJson('/api/search/suggest?q=linen');

        $durationMs = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'products',
                    'categories',
                ],
            ]);

        $totalItems = count($response->json('data.products')) + count($response->json('data.categories'));
        $this->assertLessThanOrEqual(6, $totalItems);
        $this->assertLessThan(200, $durationMs);
    }

    public function test_product_manager_can_duplicate_product_and_bulk_edit_variants(): void
    {
        $pm = User::where('email', 'product@shop.test')->first();
        $product = Product::whereIn('status', ['active', 'published'])->with('variants')->first();

        // 1. Duplicate Product
        $dupResponse = $this->actingAs($pm)
            ->postJson("/api/admin/products/{$product->id}/duplicate");

        $dupResponse->assertStatus(201)
            ->assertJsonPath('data.name', "{$product->name} (Copy)");

        $cloneId = $dupResponse->json('data.id');
        $clone = Product::find($cloneId);
        $this->assertNotNull($clone);

        // 2. Bulk edit variants on cloned product
        $bulkResponse = $this->actingAs($pm)
            ->postJson("/api/admin/products/{$clone->id}/variants/bulk-edit", [
                'action' => 'set_price',
                'value' => 89.50,
            ]);

        $bulkResponse->assertStatus(200);

        foreach ($clone->variants as $variant) {
            $this->assertEquals(89.50, (float) $variant->price);
        }
    }

    public function test_order_manager_cannot_create_or_delete_products_returns_403(): void
    {
        $om = User::where('email', 'order@shop.test')->first();
        $product = Product::first();

        $this->actingAs($om)
            ->postJson('/api/admin/products', ['name' => 'Forbidden Shirt', 'base_price' => 50])
            ->assertStatus(403);

        $this->actingAs($om)
            ->deleteJson("/api/admin/products/{$product->id}")
            ->assertStatus(403);
    }

    public function test_product_image_upload_generates_three_webp_sizes(): void
    {
        Storage::fake('public');
        $pm = User::where('email', 'product@shop.test')->first();

        $imageFile = UploadedFile::fake()->image('raw_photo.jpg', 1800, 1200);

        $response = $this->actingAs($pm)
            ->postJson('/api/admin/products/upload-image', ['image' => $imageFile]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'path',
                'thumb_path',
                'medium_path',
                'large_path',
                'url',
                'thumb_url',
                'medium_url',
                'large_url',
            ]);

        $thumbPath = $response->json('thumb_path');
        $mediumPath = $response->json('medium_path');
        $largePath = $response->json('large_path');

        $this->assertStringEndsWith('.webp', $thumbPath);
        $this->assertStringEndsWith('.webp', $mediumPath);
        $this->assertStringEndsWith('.webp', $largePath);

        Storage::disk('public')->assertExists($thumbPath);
        Storage::disk('public')->assertExists($mediumPath);
        Storage::disk('public')->assertExists($largePath);
    }

    public function test_acceptance_check_create_advanced_product_with_variants_media_and_sections(): void
    {
        $pm = User::where('email', 'product@shop.test')->first();
        $category = Category::first();
        $brand = Brand::first();

        // 3 colors x 3 sizes = 9 variants
        $colors = ['Oatmeal', 'Sage Green', 'Midnight Navy'];
        $sizes = ['Small', 'Medium', 'Large'];

        $variantsData = [];
        $skuBase = 'TEST-LINEN-'.strtoupper(fake()->lexify('????'));

        foreach ($colors as $cIndex => $color) {
            foreach ($sizes as $sIndex => $size) {
                $variantPrice = 75.00 + ($cIndex * 10) + ($sIndex * 5); // Different prices per variant
                $variantsData[] = [
                    'sku' => "{$skuBase}-{$color[0]}-{$size[0]}",
                    'price' => $variantPrice,
                    'compare_at_price' => $variantPrice + 20,
                    'stock_qty' => 15,
                    'option_values_json' => ['Color' => $color, 'Size' => $size],
                ];
            }
        }

        $payload = [
            'name' => 'Artisan Relaxed Linen Shirt',
            'sku' => $skuBase,
            'category_id' => $category->id,
            'brand_id' => $brand ? $brand->id : null,
            'base_price' => 75.00,
            'compare_at_price' => 95.00,
            'status' => 'active',
            'is_featured' => true,
            'short_description' => 'Breathable French linen button-down with natural horn buttons.',
            'description' => '<p>Woven from Normandy flax. <script>alert("hack")</script></p>',
            'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
            'options' => [
                [
                    'name' => 'Color',
                    'values' => [
                        ['name' => 'Oatmeal', 'hex_color' => '#E8E1D5'],
                        ['name' => 'Sage Green', 'hex_color' => '#879785'],
                        ['name' => 'Midnight Navy', 'hex_color' => '#1B263B'],
                    ],
                ],
                [
                    'name' => 'Size',
                    'values' => [
                        ['name' => 'Small'],
                        ['name' => 'Medium'],
                        ['name' => 'Large'],
                    ],
                ],
            ],
            'variants' => $variantsData,
            'images' => [
                ['url' => 'https://images.unsplash.com/photo-1598033129183-c4f50c736f10?w=1200', 'alt' => 'Front view', 'is_primary' => true],
                ['url' => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=1200', 'alt' => 'Back view'],
                ['url' => 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=1200', 'alt' => 'Fabric detail'],
                ['url' => 'https://images.unsplash.com/photo-1603252109303-2751441dd157?w=1200', 'alt' => 'Styling view'],
            ],
            'sections' => [
                ['title' => 'Product Details', 'content' => '<p>Relaxed chest, dropped shoulder seam, curved hemline.</p>', 'is_open_by_default' => true],
                ['title' => 'Materials & Care', 'content' => '<p>100% French flax linen. Machine wash cold, line dry in shade.</p>'],
                ['title' => 'Shipping & Returns', 'content' => '<p>Complimentary carbon-neutral standard delivery on orders over $75.</p>'],
            ],
            'specs' => [
                ['label' => 'Fabric Weight', 'value' => '185 GSM'],
                ['label' => 'Origin', 'value' => 'Normandy, France'],
                ['label' => 'Fit', 'value' => 'Relaxed / Oversized'],
            ],
        ];

        // 1. Create product via Admin API
        $response = $this->actingAs($pm)
            ->postJson('/api/admin/products', $payload);

        $response->assertStatus(201);
        $productId = $response->json('data.id');
        $slug = $response->json('data.slug');

        $createdProduct = Product::find($productId);
        $this->assertNotNull($createdProduct);

        // Verify HTML sanitization stripped the <script>
        $this->assertStringNotContainsString('<script>', $createdProduct->description);

        // Verify 9 variants were saved
        $this->assertCount(9, $createdProduct->variants);

        // Verify 4 images were saved
        $this->assertCount(4, $createdProduct->images);

        // Verify 3 sections were saved
        $this->assertCount(3, $createdProduct->detailsSections);

        // Verify video URL saved
        $this->assertNotEmpty($createdProduct->video_url);

        // 2. Query product via Public API under 200ms
        $startTime = microtime(true);
        $publicResponse = $this->getJson("/api/products/{$slug}");
        $durationMs = (microtime(true) - $startTime) * 1000;

        $publicResponse->assertStatus(200);
        $this->assertLessThan(200, $durationMs, "Single product details took {$durationMs}ms, exceeding 200ms");

        $prodData = $publicResponse->json('data.product');
        $this->assertCount(9, $prodData['variants']);
        $this->assertCount(4, $prodData['images']);
        $this->assertCount(3, $prodData['sections']);
        $this->assertEquals('Artisan Relaxed Linen Shirt', $prodData['name']);

        // 3. Search for product via Public Catalog search under 200ms
        $searchStartTime = microtime(true);
        $searchResponse = $this->getJson('/api/products?search='.urlencode('Relaxed Linen'));
        $searchDurationMs = (microtime(true) - $searchStartTime) * 1000;

        $searchResponse->assertStatus(200);
        $this->assertLessThan(200, $searchDurationMs, "Catalog search took {$searchDurationMs}ms, exceeding 200ms");

        $searchResults = $searchResponse->json('data');
        $this->assertNotEmpty($searchResults);
        $this->assertEquals($productId, $searchResults[0]['id']);
    }

    public function test_catalog_filters_endpoint_returns_facets(): void
    {
        $response = $this->getJson('/api/catalog/filters');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'brands' => [
                        '*' => ['id', 'name', 'slug', 'products_count'],
                    ],
                    'categories' => [
                        '*' => ['id', 'name', 'slug', 'products_count'],
                    ],
                    'colors',
                    'sizes',
                    'price_range' => ['min', 'max'],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.brands'));
        $this->assertNotEmpty($response->json('data.categories'));
    }

    public function test_catalog_filtering_by_rating_and_sorting_by_rating(): void
    {
        // 1. Filter by rating 4+
        $response = $this->getJson('/api/products?rating=4');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $prod) {
            $this->assertGreaterThanOrEqual(4.0, (float) $prod['rating']);
        }

        // 2. Sort by rating
        $sortResponse = $this->getJson('/api/products?sort=rating');
        $sortResponse->assertStatus(200);
        $sortData = $sortResponse->json('data');
        $this->assertNotEmpty($sortData);
        $this->assertGreaterThanOrEqual($sortData[count($sortData) - 1]['rating'], $sortData[0]['rating']);
    }

    public function test_customer_can_submit_product_review(): void
    {
        $product = Product::whereIn('status', ['active', 'published'])->first();
        $this->assertNotNull($product);

        $response = $this->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 5,
            'title' => 'Stunning linen texture',
            'body' => 'Genuinely surprised by the substantial weight and breathable finish of this garment.',
            'author_name' => 'Sara Jenkins',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'author_name',
                'rating',
                'title',
                'body',
            ],
        ]);

        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->id,
            'title' => 'Stunning linen texture',
            'rating' => 5,
        ]);
    }

    public function test_customer_can_vote_review_helpful(): void
    {
        $review = Review::where('is_approved', true)->first();
        $this->assertNotNull($review);

        $initialCount = $review->helpful_count;

        $response = $this->postJson("/api/reviews/{$review->id}/helpful");

        $response->assertStatus(200);
        $this->assertEquals($initialCount + 1, $response->json('helpful_count'));
    }
}

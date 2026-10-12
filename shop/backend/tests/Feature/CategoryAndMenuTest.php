<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\MenuService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryAndMenuTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Category::where('slug', 'like', 'artisan-ceramics%')->delete();
        Category::where('slug', 'sub-test')->delete();
        MenuItem::where('label', 'Women')->delete();
        CategoryService::clearCache();
        MenuService::clearCache();
    }

    public function test_public_categories_endpoint_returns_tree_structure(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'image',
                        'sort_order',
                        'products_count',
                        'children',
                    ],
                ],
            ]);
    }

    public function test_public_category_slug_endpoint_returns_details_and_breadcrumbs(): void
    {
        $category = Category::whereNotNull('parent_id')->first();
        if (! $category) {
            $parent = Category::first();
            $category = Category::create([
                'name' => 'Sub Test',
                'slug' => 'sub-test',
                'parent_id' => $parent->id,
                'is_active' => true,
            ]);
        }

        $response = $this->getJson("/api/categories/{$category->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'category' => ['id', 'name', 'slug', 'products_count'],
                    'breadcrumbs' => [
                        '*' => ['id', 'name', 'slug'],
                    ],
                    'children',
                ],
            ]);

        // Breadcrumbs should include at least 2 levels (parent and current)
        $breadcrumbs = $response->json('data.breadcrumbs');
        $this->assertGreaterThanOrEqual(2, count($breadcrumbs));
        $this->assertEquals($category->slug, end($breadcrumbs)['slug']);
    }

    public function test_product_manager_can_create_and_reorder_categories(): void
    {
        $pm = User::where('email', 'product@shop.test')->first();

        // 1. Create Category
        $response = $this->actingAs($pm)
            ->postJson('/api/admin/categories', [
                'name' => 'Artisan Ceramics',
                'description' => 'Handcrafted stoneware',
                'is_active' => true,
                'is_featured' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', 'artisan-ceramics');

        $catId = $response->json('data.id');

        // 2. Reorder
        $reorderResponse = $this->actingAs($pm)
            ->postJson('/api/admin/categories/reorder', [
                'items' => [
                    ['id' => $catId, 'sort_order' => 99],
                ],
            ]);

        $reorderResponse->assertStatus(200);
        $this->assertEquals(99, Category::find($catId)->sort_order);
    }

    public function test_order_manager_cannot_manage_categories_returns_403(): void
    {
        $om = User::where('email', 'order@shop.test')->first();

        $this->actingAs($om)
            ->getJson('/api/admin/categories')
            ->assertStatus(403);

        $this->actingAs($om)
            ->postJson('/api/admin/categories', ['name' => 'Forbidden Category'])
            ->assertStatus(403);
    }

    public function test_category_image_upload_with_svg_sanitization(): void
    {
        Storage::fake('public');
        $pm = User::where('email', 'product@shop.test')->first();

        // Malicious SVG
        $badSvg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert("hack")</script></svg>';
        $badFile = UploadedFile::fake()->createWithContent('malicious.svg', $badSvg);

        $this->actingAs($pm)
            ->postJson('/api/admin/categories/upload-image', ['image' => $badFile])
            ->assertStatus(422);

        // Safe Image
        $safeFile = UploadedFile::fake()->image('tile.png', 400, 400);
        $goodResponse = $this->actingAs($pm)
            ->postJson('/api/admin/categories/upload-image', ['image' => $safeFile]);

        $goodResponse->assertStatus(200)
            ->assertJsonStructure(['url', 'path']);
    }

    public function test_acceptance_check_create_women_mega_menu_with_4_columns_and_featured_image(): void
    {
        $admin = User::where('email', 'admin@shop.test')->first();

        $mainMenu = Menu::firstOrCreate(['slug' => 'main'], ['name' => 'Main Navigation', 'is_active' => true]);

        // 1. Create top-level "Women" item with a featured image card
        $topItemResponse = $this->actingAs($admin)
            ->postJson("/api/admin/menus/{$mainMenu->id}/items", [
                'label' => 'Women',
                'type' => 'category',
                'url' => '/category/women',
                'badge_text' => 'Trending',
                'sort_order' => 1,
                'image' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800',
                'featured_title' => 'Spring Capsule Collection',
                'featured_button_text' => 'Explore Lookbook',
                'featured_button_url' => '/category/spring-capsule',
                'is_active' => true,
            ]);

        $topItemResponse->assertStatus(201);
        $womenItemId = $topItemResponse->json('data.id');

        // 2. Create 4 columns of sub-categories
        $columnData = [
            1 => ['title' => 'Clothing', 'items' => ['Dresses & Jumpsuits', 'Tops & Shirts', 'Knitwear & Sweaters']],
            2 => ['title' => 'Shoes', 'items' => ['Leather Sandals', 'Ankle Boots', 'Loafers']],
            3 => ['title' => 'Accessories', 'items' => ['Silk Scarves', 'Leather Belts', 'Everyday Jewelry']],
            4 => ['title' => 'Curated Edits', 'items' => ['New Arrivals', 'Best Sellers', 'Organic Essentials']],
        ];

        foreach ($columnData as $colIndex => $column) {
            // Group header
            $groupResponse = $this->actingAs($admin)
                ->postJson("/api/admin/menus/{$mainMenu->id}/items", [
                    'label' => $column['title'],
                    'type' => 'mega_group',
                    'parent_id' => $womenItemId,
                    'column_index' => $colIndex,
                    'sort_order' => 1,
                    'is_active' => true,
                ]);
            $groupId = $groupResponse->json('data.id');

            // Sub-category items under this column group
            foreach ($column['items'] as $itemIndex => $subLabel) {
                $this->actingAs($admin)
                    ->postJson("/api/admin/menus/{$mainMenu->id}/items", [
                        'label' => $subLabel,
                        'type' => 'category',
                        'parent_id' => $groupId,
                        'column_index' => $colIndex,
                        'url' => '/category/'.strtolower(str_replace([' ', '&'], ['-', 'and'], $subLabel)),
                        'sort_order' => $itemIndex + 1,
                        'is_active' => true,
                    ]);
            }
        }

        // 3. Acceptance Check: Verify public menu API returns the exact mega structure
        $publicMenuResponse = $this->getJson('/api/menu/main');

        $publicMenuResponse->assertStatus(200);

        $items = $publicMenuResponse->json('data.items');
        $womenItem = collect($items)->firstWhere('label', 'Women');

        $this->assertNotNull($womenItem, 'Women top-level item must exist');
        $this->assertTrue($womenItem['is_mega'], 'Women item must be flagged as is_mega');

        // Verify featured card
        $this->assertNotNull($womenItem['featured_card']);
        $this->assertEquals('Spring Capsule Collection', $womenItem['featured_card']['title']);
        $this->assertEquals('Explore Lookbook', $womenItem['featured_card']['button_text']);
        $this->assertEquals('/category/spring-capsule', $womenItem['featured_card']['button_url']);
        $this->assertStringContainsString('unsplash.com', $womenItem['featured_card']['image']);

        // Verify 4 columns exist
        $this->assertCount(4, $womenItem['columns'], 'Women mega item must have 4 columns');

        // Verify Column 1
        $col1 = collect($womenItem['columns'])->firstWhere('column_index', 1);
        $this->assertEquals('Clothing', $col1['title']);
        $this->assertCount(3, $col1['items']);
        $this->assertEquals('Dresses & Jumpsuits', $col1['items'][0]['label']);

        // Verify Column 4
        $col4 = collect($womenItem['columns'])->firstWhere('column_index', 4);
        $this->assertEquals('Curated Edits', $col4['title']);
        $this->assertCount(3, $col4['items']);
        $this->assertEquals('New Arrivals', $col4['items'][0]['label']);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOptionType;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Brands
        $brands = [
            ['name' => 'Kanso Studio', 'slug' => 'kanso-studio', 'logo' => null],
            ['name' => 'Atelier Nord', 'slug' => 'atelier-nord', 'logo' => null],
            ['name' => 'Hygge Living', 'slug' => 'hygge-living', 'logo' => null],
            ['name' => 'Verdant Craft', 'slug' => 'verdant-craft', 'logo' => null],
            ['name' => 'Common Thread', 'slug' => 'common-thread', 'logo' => null],
        ];
        $brandModels = [];
        foreach ($brands as $b) {
            $brandModels[$b['name']] = Brand::create($b);
        }

        // 2. Create Tags
        $tagNames = ['Bestseller', 'New Arrival', 'Organic', 'Handmade', 'Sustainable', 'Minimalist', 'Water-resistant', 'Gift Idea'];
        $tagModels = [];
        foreach ($tagNames as $tn) {
            $tagModels[$tn] = Tag::create(['name' => $tn, 'slug' => Str::slug($tn)]);
        }

        $categories = Category::whereNotNull('parent_id')->get();
        if ($categories->isEmpty()) {
            $categories = Category::all();
        }

        // Sample product catalog list
        $productCatalog = [
            // Apparel
            ['name' => 'Organic French Linen Overshirt', 'cat' => 'Tops & Shirts', 'brand' => 'Common Thread', 'price' => 110.00, 'compare' => 135.00, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Heavyweight Pima Cotton Tee', 'cat' => 'Tops & Shirts', 'brand' => 'Common Thread', 'price' => 42.00, 'compare' => null, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Relaxed Pleated Chino Trouser', 'cat' => 'Trousers & Pants', 'brand' => 'Atelier Nord', 'price' => 128.00, 'compare' => null, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Merino Wool Ribbed Cardigan', 'cat' => 'Knitwear', 'brand' => 'Atelier Nord', 'price' => 165.00, 'compare' => 195.00, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Waterproof City Mac Coat', 'cat' => 'Outerwear', 'brand' => 'Kanso Studio', 'price' => 240.00, 'compare' => 280.00, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1544022613-e87ca75a784a?auto=format&fit=crop&w=800&q=80'],

            // Home & Living
            ['name' => 'Wheel-Thrown Stoneware Vase', 'cat' => 'Ceramics & Vases', 'brand' => 'Verdant Craft', 'price' => 64.00, 'compare' => null, 'has_variants' => false, 'img' => 'https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Matte Terracotta Fluted Planter', 'cat' => 'Ceramics & Vases', 'brand' => 'Verdant Craft', 'price' => 48.00, 'compare' => 58.00, 'has_variants' => false, 'img' => 'https://images.unsplash.com/photo-1485955900006-10f4d324d411?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Brass Dimmer Table Lamp', 'cat' => 'Lighting', 'brand' => 'Kanso Studio', 'price' => 145.00, 'compare' => null, 'has_variants' => false, 'img' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Washed Belgian Linen Duvet Set', 'cat' => 'Bedding', 'brand' => 'Hygge Living', 'price' => 210.00, 'compare' => 245.00, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Waffle Weave Wool Throw Blanket', 'cat' => 'Throw Pillows & Blankets', 'brand' => 'Hygge Living', 'price' => 88.00, 'compare' => null, 'has_variants' => false, 'img' => 'https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?auto=format&fit=crop&w=800&q=80'],

            // Bags & Leather
            ['name' => 'Vegetable-Tanned Daily Tote', 'cat' => 'Tote Bags', 'brand' => 'Atelier Nord', 'price' => 185.00, 'compare' => null, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Waxed Canvas Commuter Rucksack', 'cat' => 'Backpacks & Rucksacks', 'brand' => 'Kanso Studio', 'price' => 175.00, 'compare' => 210.00, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Pebbled Leather Bifold Wallet', 'cat' => 'Wallets & Cardholders', 'brand' => 'Atelier Nord', 'price' => 65.00, 'compare' => null, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Minimalist Card Sleeve', 'cat' => 'Wallets & Cardholders', 'brand' => 'Common Thread', 'price' => 38.00, 'compare' => 45.00, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?auto=format&fit=crop&w=800&q=80'],

            // Footwear
            ['name' => 'Court Low-Top Leather Sneaker', 'cat' => 'Everyday Sneakers', 'brand' => 'Common Thread', 'price' => 135.00, 'compare' => null, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Suede Hand-Stitched Penny Loafer', 'cat' => 'Leather Loafers', 'brand' => 'Atelier Nord', 'price' => 195.00, 'compare' => 225.00, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1533867617858-e7b97e060509?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Shearling-Lined Suede Mules', 'cat' => 'Mules & Slides', 'brand' => 'Hygge Living', 'price' => 115.00, 'compare' => null, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1603808033192-082d6919d3e1?auto=format&fit=crop&w=800&q=80'],

            // Kitchen & Dining
            ['name' => 'Speckled Ceramic Coffee Mug (Set of 2)', 'cat' => 'Drinkware & Mugs', 'brand' => 'Verdant Craft', 'price' => 46.00, 'compare' => null, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Handmade Stoneware Pasta Bowls (Set of 4)', 'cat' => 'Dinner Plates & Bowls', 'brand' => 'Verdant Craft', 'price' => 92.00, 'compare' => 110.00, 'has_variants' => false, 'img' => 'https://images.unsplash.com/photo-1574343635070-5b6d5108f9d0?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Stonewashed French Linen Napkins (Set of 4)', 'cat' => 'Table Linens', 'brand' => 'Hygge Living', 'price' => 36.00, 'compare' => null, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Cast Iron Skillet with Walnut Handle', 'cat' => 'Dinner Plates & Bowls', 'brand' => 'Kanso Studio', 'price' => 125.00, 'compare' => null, 'has_variants' => false, 'img' => 'https://images.unsplash.com/photo-1584990347449-a6e4d0b1626f?auto=format&fit=crop&w=800&q=80'],

            // Wellness & Care
            ['name' => 'Cedar & Wild Fig Botanical Candle', 'cat' => 'Botanical Candles', 'brand' => 'Verdant Craft', 'price' => 34.00, 'compare' => null, 'has_variants' => false, 'img' => 'https://images.unsplash.com/photo-1603006905003-be475563bc59?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Hinoki Cypress Essential Oil Mist', 'cat' => 'Room & Linen Sprays', 'brand' => 'Kanso Studio', 'price' => 28.00, 'compare' => null, 'has_variants' => false, 'img' => 'https://images.unsplash.com/photo-1616949755610-8c9bbc08f138?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Cold-Pressed Botanical Bar Soap Trio', 'cat' => 'Organic Soaps & Washes', 'brand' => 'Verdant Craft', 'price' => 26.00, 'compare' => 30.00, 'has_variants' => false, 'img' => 'https://images.unsplash.com/photo-1607006314645-0d4d7b578c77?auto=format&fit=crop&w=800&q=80'],

            // Workspace & Paper
            ['name' => 'Layflat Thread-Bound Daily Journal', 'cat' => 'Notebooks & Journals', 'brand' => 'Kanso Studio', 'price' => 28.00, 'compare' => null, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Solid Brass Precision Ballpoint Pen', 'cat' => 'Writing Instruments', 'brand' => 'Kanso Studio', 'price' => 58.00, 'compare' => 68.00, 'has_variants' => false, 'img' => 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Solid Oak Modular Desk Organiser Tray', 'cat' => 'Desk Organizers', 'brand' => 'Atelier Nord', 'price' => 74.00, 'compare' => null, 'has_variants' => false, 'img' => 'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?auto=format&fit=crop&w=800&q=80'],

            // Travel & Utility
            ['name' => 'Overnight Canvas Weekender Duffle', 'cat' => 'Weekender Duffels', 'brand' => 'Common Thread', 'price' => 160.00, 'compare' => 190.00, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Vacuum Insulated Steel Travel Tumbler (500ml)', 'cat' => 'Insulated Flasks', 'brand' => 'Kanso Studio', 'price' => 38.00, 'compare' => null, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Waterproof Zip Dopp Kit', 'cat' => 'Dopp Kits & Pouches', 'brand' => 'Atelier Nord', 'price' => 48.00, 'compare' => null, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=800&q=80'],

            // Additional Products to reach 40 realistic products
            ['name' => 'Organic Cotton Oxford Button-Down', 'cat' => 'Tops & Shirts', 'brand' => 'Common Thread', 'price' => 95.00, 'compare' => null, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Textured Linen Summer Shorts', 'cat' => 'Trousers & Pants', 'brand' => 'Common Thread', 'price' => 78.00, 'compare' => 90.00, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Chunky Cashmere Beanie Hat', 'cat' => 'Knitwear', 'brand' => 'Atelier Nord', 'price' => 65.00, 'compare' => null, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1576871337632-b9aef4c17ab9?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Scandi Minimalist Wall Clock', 'cat' => 'Ceramics & Vases', 'brand' => 'Kanso Studio', 'price' => 82.00, 'compare' => null, 'has_variants' => false, 'img' => 'https://images.unsplash.com/photo-1563861826100-9cb868fdbe1c?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Archival Linen Photo Album', 'cat' => 'Notebooks & Journals', 'brand' => 'Hygge Living', 'price' => 52.00, 'compare' => null, 'has_variants' => false, 'img' => 'https://images.unsplash.com/photo-1532012164546-f432f2e3777f?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Handwoven Seagrass Storage Basket', 'cat' => 'Throw Pillows & Blankets', 'brand' => 'Hygge Living', 'price' => 45.00, 'compare' => 55.00, 'has_variants' => false, 'img' => 'https://images.unsplash.com/photo-1595428774223-ef52624120d2?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Ergonomic Wooden Desk Riser', 'cat' => 'Desk Organizers', 'brand' => 'Atelier Nord', 'price' => 110.00, 'compare' => null, 'has_variants' => false, 'img' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Matte Black Pour-Over Kettle (800ml)', 'cat' => 'Drinkware & Mugs', 'brand' => 'Kanso Studio', 'price' => 68.00, 'compare' => 80.00, 'has_variants' => false, 'img' => 'https://images.unsplash.com/photo-1517668808822-9ebb02f2a0e6?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'French Terry Everyday Sweatshirt', 'cat' => 'Tops & Shirts', 'brand' => 'Common Thread', 'price' => 85.00, 'compare' => null, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?auto=format&fit=crop&w=800&q=80'],
            ['name' => 'Canvas Crossbody Utility Pouch', 'cat' => 'Tote Bags', 'brand' => 'Kanso Studio', 'price' => 55.00, 'compare' => null, 'has_variants' => true, 'img' => 'https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=800&q=80'],
        ];

        $productModels = [];

        foreach ($productCatalog as $index => $item) {
            $skuNum = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            $sku = "PRD-{$skuNum}";

            // Find matching category
            $category = Category::where('name', $item['cat'])->first() ?: $categories->random();
            $brand = $brandModels[$item['brand']] ?? null;

            $product = Product::create([
                'category_id' => $category->id,
                'brand_id' => $brand?->id,
                'name' => $item['name'],
                'slug' => Str::slug($item['name']).'-'.strtolower($sku),
                'sku' => $sku,
                'short_description' => 'A thoughtfully created piece with refined proportions and premium tactile finishes.',
                'description' => '<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>',
                'status' => 'active',
                'is_featured' => ($index < 8),
                'base_price' => $item['price'],
                'compare_at_price' => $item['compare'],
                'cost_price' => round($item['price'] * 0.45, 2),
                'track_stock' => true,
                'stock_qty' => rand(15, 60),
                'low_stock_threshold' => 5,
                'weight' => rand(300, 1500) / 1000,
                'length' => rand(15, 40),
                'width' => rand(10, 30),
                'height' => rand(5, 20),
                'tax_class' => 'standard',
                'seo_title' => "{$item['name']} | Boutique Essentials",
                'seo_description' => "Shop {$item['name']}. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.",
            ]);

            // Sync category & tags
            $product->categories()->syncWithoutDetaching([$category->id]);
            $randomTags = collect($tagModels)->random(rand(2, 3))->pluck('id')->toArray();
            $product->tags()->sync($randomTags);

            // Images
            $product->images()->create([
                'path' => null,
                'external_url' => $item['img'],
                'alt' => "{$item['name']} primary view",
                'sort_order' => 1,
                'is_primary' => true,
            ]);
            $product->images()->create([
                'path' => null,
                'external_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',
                'alt' => "{$item['name']} detail view",
                'sort_order' => 2,
                'is_primary' => false,
            ]);

            // Item Details Sections (accordions)
            $product->detailsSections()->createMany([
                [
                    'title' => 'Materials & Craft',
                    'content' => '<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',
                    'sort_order' => 1,
                    'is_open_by_default' => true,
                ],
                [
                    'title' => 'Dimensions & Care',
                    'content' => '<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',
                    'sort_order' => 2,
                    'is_open_by_default' => false,
                ],
                [
                    'title' => 'Shipping & Returns',
                    'content' => '<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',
                    'sort_order' => 3,
                    'is_open_by_default' => false,
                ],
            ]);

            // Product Specs
            $product->specs()->createMany([
                ['label' => 'Origin', 'value' => 'Handcrafted in Portugal', 'sort_order' => 1],
                ['label' => 'Material', 'value' => 'Certified sustainable organic blend', 'sort_order' => 2],
                ['label' => 'Packaging', 'value' => '100% recyclable post-consumer box', 'sort_order' => 3],
            ]);

            // Variants (for items marked has_variants)
            if ($item['has_variants']) {
                $optionColor = ProductOptionType::create([
                    'product_id' => $product->id,
                    'name' => 'Color',
                    'sort_order' => 1,
                ]);

                $val1 = ProductOptionValue::create(['option_type_id' => $optionColor->id, 'value' => 'Sage Green', 'color_hex' => '#2F5D50']);
                $val2 = ProductOptionValue::create(['option_type_id' => $optionColor->id, 'value' => 'Warm Sand', 'color_hex' => '#D4C5B9']);

                $v1 = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => "{$sku}-SAGE",
                    'price' => $item['price'],
                    'compare_at_price' => $item['compare'],
                    'stock_qty' => 18,
                    'is_active' => true,
                    'option_values_json' => ['Color' => 'Sage Green'],
                ]);
                $v1->optionValues()->attach($val1->id);

                $v2 = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => "{$sku}-SAND",
                    'price' => $item['price'],
                    'compare_at_price' => $item['compare'],
                    'stock_qty' => 22,
                    'is_active' => true,
                    'option_values_json' => ['Color' => 'Warm Sand'],
                ]);
                $v2->optionValues()->attach($val2->id);
            }

            $productModels[] = $product;
        }

        // 3. Create 20 realistic reviews
        $reviewTexts = [
            ['title' => 'Exceptional build quality', 'body' => 'The tactile feel and finish exceeded my expectations. Arrived in plastic-free packaging within three days.'],
            ['title' => 'A calm and beautiful daily essential', 'body' => 'Exactly as described. Subtle color, clean seams, and feels substantial in hand.'],
            ['title' => 'Worth every penny', 'body' => 'You can genuinely tell the difference in craftsmanship compared to generic department store alternatives.'],
            ['title' => 'Understated elegance', 'body' => 'Minimalist design that fits into our home effortlessly. Very happy with this purchase.'],
            ['title' => 'Sturdy and well-proportioned', 'body' => 'Delivered promptly and looks gorgeous on the counter. Would happily recommend.'],
            ['title' => 'Thoughtful packaging and great texture', 'body' => 'The natural material feels soft yet durable. Has held up beautifully after everyday use.'],
            ['title' => 'My new favourite piece', 'body' => 'Subtle styling and great attention to detail. Excellent customer care as well.'],
            ['title' => 'High quality and prompt shipping', 'body' => 'Great experience ordering here. Everything was seamless from checkout to unboxing.'],
        ];

        $customerUsers = User::role('customer')->get();
        $sampleAuthors = ['Claire Henderson', 'Marcus Thorne', 'Avery Brooks', 'Elena Vance', 'Julian Hayes', 'Sora Tanaka', 'David Miller', 'Hannah Lindqvist'];

        for ($i = 0; $i < 20; $i++) {
            $product = $productModels[$i % count($productModels)];
            $rev = $reviewTexts[$i % count($reviewTexts)];
            $author = $sampleAuthors[$i % count($sampleAuthors)];

            Review::create([
                'product_id' => $product->id,
                'user_id' => $customerUsers->isNotEmpty() ? $customerUsers->random()->id : null,
                'author_name' => $author,
                'author_avatar' => null,
                'rating' => rand(4, 5),
                'title' => $rev['title'],
                'body' => $rev['body'],
                'is_approved' => true,
                'is_admin_created' => false,
                'review_date' => now()->subDays(rand(1, 45)),
                'helpful_count' => rand(1, 14),
            ]);
        }
    }
}

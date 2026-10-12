<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categoriesTree = [
            [
                'name' => 'Apparel',
                'description' => 'Mindfully tailored organic garments made for comfort and daily longevity.',
                'image' => 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?auto=format&fit=crop&w=600&q=80',
                'icon' => 'Shirt',
                'is_featured' => true,
                'children' => [
                    ['name' => 'Tops & Shirts', 'description' => 'Crisp linen and organic combed cotton essentials.'],
                    ['name' => 'Trousers & Pants', 'description' => 'Pleated chinos, relaxed denim, and drawstring trousers.'],
                    ['name' => 'Knitwear', 'description' => 'Warm merino wool and alpaca cardigans.'],
                    ['name' => 'Outerwear', 'description' => 'Weather-ready overshirts and minimalist trench coats.'],
                ],
            ],
            [
                'name' => 'Home & Living',
                'description' => 'Tactile ceramic objects, warm lighting, and calm home accents.',
                'image' => 'https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=600&q=80',
                'icon' => 'Home',
                'is_featured' => true,
                'children' => [
                    ['name' => 'Ceramics & Vases', 'description' => 'Wheel-thrown stoneware and artisanal decorative vessels.'],
                    ['name' => 'Lighting', 'description' => 'Dimmable warm glow lamps and brass sconces.'],
                    ['name' => 'Bedding', 'description' => 'Pre-washed French flax linen duvet sets.'],
                    ['name' => 'Throw Pillows & Blankets', 'description' => 'Handwoven waffle cotton throws and wool cushions.'],
                ],
            ],
            [
                'name' => 'Bags & Leather',
                'description' => 'Vegetable-tanned leather goods crafted to age gracefully with patina.',
                'image' => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=600&q=80',
                'icon' => 'Briefcase',
                'is_featured' => true,
                'children' => [
                    ['name' => 'Tote Bags', 'description' => 'Heavyweight organic canvas and full-grain leather totes.'],
                    ['name' => 'Backpacks & Rucksacks', 'description' => 'Structured commuter backpacks with padded laptop sleeves.'],
                    ['name' => 'Wallets & Cardholders', 'description' => 'Slim bifold wallets and tactile pebble-grain card sleeves.'],
                ],
            ],
            [
                'name' => 'Footwear',
                'description' => 'Ergonomic soles and understated silhouettes designed for city walking.',
                'image' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=600&q=80',
                'icon' => 'Footprints',
                'is_featured' => true,
                'children' => [
                    ['name' => 'Everyday Sneakers', 'description' => 'Minimal low-tops with natural rubber cupsoles.'],
                    ['name' => 'Leather Loafers', 'description' => 'Hand-stitched penny loafers with cushioned insoles.'],
                    ['name' => 'Mules & Slides', 'description' => 'Slip-on leather mules for effortless indoor and outdoor ease.'],
                ],
            ],
            [
                'name' => 'Kitchen & Dining',
                'description' => 'Functional culinary tools and durable handcrafted tableware.',
                'image' => 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=600&q=80',
                'icon' => 'Utensils',
                'is_featured' => true,
                'children' => [
                    ['name' => 'Drinkware & Mugs', 'description' => 'Hand-glazed ceramic mugs and heat-resistant borosilicate glasses.'],
                    ['name' => 'Dinner Plates & Bowls', 'description' => 'Matte stoneware sets designed for daily gatherings.'],
                    ['name' => 'Table Linens', 'description' => 'Stonewashed linen napkins and neutral table runners.'],
                ],
            ],
            [
                'name' => 'Wellness & Care',
                'description' => 'Plant-powered self-care goods, clean scents, and bath rituals.',
                'image' => 'https://images.unsplash.com/photo-1608248597359-59752b047514?auto=format&fit=crop&w=600&q=80',
                'icon' => 'Sparkles',
                'is_featured' => true,
                'children' => [
                    ['name' => 'Botanical Candles', 'description' => 'Soy-wax candles infused with cedarwood, fig, and vetiver.'],
                    ['name' => 'Organic Soaps & Washes', 'description' => 'Cold-processed soaps with natural botanical oils.'],
                    ['name' => 'Room & Linen Sprays', 'description' => 'Subtle essential oil mist to refresh living spaces.'],
                ],
            ],
            [
                'name' => 'Workspace & Paper',
                'description' => 'Tools for focused thought, writing, and mindful desk organization.',
                'image' => 'https://images.unsplash.com/photo-1585776245991-cf89dd7fc73a?auto=format&fit=crop&w=600&q=80',
                'icon' => 'BookOpen',
                'is_featured' => true,
                'children' => [
                    ['name' => 'Notebooks & Journals', 'description' => 'Thread-bound notebooks with fountain-pen-friendly archival paper.'],
                    ['name' => 'Writing Instruments', 'description' => 'Precision rollerball pens and solid brass mechanical pencils.'],
                    ['name' => 'Desk Organizers', 'description' => 'Solid oak trays and magnetic cable holders.'],
                ],
            ],
            [
                'name' => 'Travel & Utility',
                'description' => 'Compact and weather-resistant gear built for quiet weekend getaways.',
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=600&q=80',
                'icon' => 'Compass',
                'is_featured' => true,
                'children' => [
                    ['name' => 'Weekender Duffels', 'description' => 'Water-repellent duffels with dedicated footwear compartments.'],
                    ['name' => 'Insulated Flasks', 'description' => 'Double-walled stainless steel thermal bottles.'],
                    ['name' => 'Dopp Kits & Pouches', 'description' => 'Spill-resistant toiletry organizers with brass zippers.'],
                ],
            ],
        ];

        foreach ($categoriesTree as $index => $parentData) {
            $parent = Category::create([
                'name' => $parentData['name'],
                'slug' => Str::slug($parentData['name']),
                'description' => $parentData['description'],
                'image' => $parentData['image'],
                'icon' => $parentData['icon'],
                'sort_order' => $index + 1,
                'is_active' => true,
                'is_featured' => $parentData['is_featured'],
                'seo_title' => $parentData['name'].' - Mindfully Sourced Collection',
                'seo_description' => $parentData['description'],
            ]);

            foreach ($parentData['children'] as $childIndex => $childData) {
                Category::create([
                    'parent_id' => $parent->id,
                    'name' => $childData['name'],
                    'slug' => Str::slug($childData['name']),
                    'description' => $childData['description'],
                    'image' => $parentData['image'],
                    'icon' => $parentData['icon'],
                    'sort_order' => $childIndex + 1,
                    'is_active' => true,
                    'is_featured' => false,
                    'seo_title' => $childData['name'].' - Boutique',
                    'seo_description' => $childData['description'],
                ]);
            }
        }
    }
}

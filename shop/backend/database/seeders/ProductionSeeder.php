<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Services\EmailService;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Seed only production-ready roles, permissions, default settings, navigation shells, and email templates.
     * Does NOT seed demo products or fake customer reviews.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            SettingSeeder::class,
        ]);

        // Ensure Main & Footer Menu containers exist
        Menu::firstOrCreate(
            ['slug' => 'main'],
            ['name' => 'Main Header Navigation']
        );

        Menu::firstOrCreate(
            ['slug' => 'footer'],
            ['name' => 'Footer Navigation']
        );

        // Seed all 16 default Customer & Staff email templates
        EmailService::seedDefaults();
    }
}

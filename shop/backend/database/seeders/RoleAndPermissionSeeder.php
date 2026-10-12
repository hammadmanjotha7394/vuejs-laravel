<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create permissions
        $permissions = [
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'categories.manage',
            'brands.manage',
            'orders.view',
            'orders.update',
            'orders.refund',
            'finance.view',
            'reports.view',
            'settings.view',
            'settings.update',
            'stripe.manage',
            'mail.manage',
            'users.view',
            'users.create',
            'users.update',
            'roles.manage',
            'reviews.manage',
            'chat.manage',
            'popups.manage',
            'hero.manage',
            'menus.manage',
            'pages.manage',
            'coupons.manage',
            'shipping.manage',
            'activity.view',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        // 2. Create Roles
        $superAdmin = Role::findOrCreate('super_admin', 'web');
        $superAdmin->givePermissionTo(Permission::all());

        $productManager = Role::findOrCreate('product_manager', 'web');
        $productManager->givePermissionTo([
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'categories.manage',
            'brands.manage',
            'hero.manage',
            'reviews.manage',
        ]);

        $orderManager = Role::findOrCreate('order_manager', 'web');
        $orderManager->givePermissionTo([
            'orders.view',
            'orders.update',
            'coupons.manage',
            'shipping.manage',
            'users.view',
        ]);

        $supportAgent = Role::findOrCreate('support_agent', 'web');
        $supportAgent->givePermissionTo([
            'chat.manage',
            'reviews.manage',
            'orders.view',
        ]);

        $financeManager = Role::findOrCreate('finance_manager', 'web');
        $financeManager->givePermissionTo([
            'finance.view',
            'reports.view',
            'orders.view',
            'orders.refund',
        ]);

        Role::findOrCreate('customer', 'web');

        // 3. Create Super Admin User
        $admin = User::updateOrCreate(
            ['email' => 'admin@shop.test'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password123'),
                'phone' => '+1 (555) 010-0001',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['super_admin']);

        // Create sample staff users for testing
        $pm = User::updateOrCreate(
            ['email' => 'product@shop.test'],
            [
                'name' => 'Sarah (Product Lead)',
                'password' => Hash::make('password123'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $pm->syncRoles(['product_manager']);

        $om = User::updateOrCreate(
            ['email' => 'order@shop.test'],
            [
                'name' => 'Oliver (Order Manager)',
                'password' => Hash::make('password123'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $om->syncRoles(['order_manager']);

        $customer = User::updateOrCreate(
            ['email' => 'customer@shop.test'],
            [
                'name' => 'Emma Watson',
                'password' => Hash::make('password123'),
                'phone' => '+1 (555) 019-8833',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $customer->syncRoles(['customer']);
    }
}

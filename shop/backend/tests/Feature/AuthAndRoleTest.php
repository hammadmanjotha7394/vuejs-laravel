<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthAndRoleTest extends TestCase
{
    public function test_login_successful_and_returns_roles_and_permissions(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'product@shop.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'roles',
                    'permissions',
                ],
            ]);

        $this->assertContains('product_manager', $response->json('user.roles'));
        $this->assertContains('products.view', $response->json('user.permissions'));
        $this->assertNotContains('finance.view', $response->json('user.permissions'));
    }

    public function test_product_manager_cannot_access_finance_endpoints_returns_403(): void
    {
        $pm = User::where('email', 'product@shop.test')->first();

        $response = $this->actingAs($pm)->getJson('/api/admin/finance/summary');

        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_finance_endpoints_returns_200(): void
    {
        $admin = User::where('email', 'admin@shop.test')->first();

        $response = $this->actingAs($admin)->getJson('/api/admin/finance/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'revenue_total',
                'orders_count',
                'average_order_value',
                'currency',
            ]);
    }

    public function test_super_admin_can_create_new_role_and_staff_user(): void
    {
        $admin = User::where('email', 'admin@shop.test')->first();
        $uniqueSuffix = uniqid();
        $roleName = "inv_spec_{$uniqueSuffix}";
        $userEmail = "alex_{$uniqueSuffix}@shop.test";

        // 1. Super admin creates custom role
        $roleResponse = $this->actingAs($admin)->postJson('/api/admin/roles', [
            'name' => $roleName,
            'permissions' => ['products.view', 'products.update'],
        ]);

        $roleResponse->assertStatus(201)
            ->assertJsonPath('role.name', $roleName);

        $this->assertDatabaseHas('roles', ['name' => $roleName]);

        // 2. Super admin creates new staff user with the custom role
        $userResponse = $this->actingAs($admin)->postJson('/api/admin/users', [
            'name' => 'Alex Inventory',
            'email' => $userEmail,
            'phone' => '+15551239999',
            'password' => 'password123',
            'role' => $roleName,
            'is_active' => true,
        ]);

        $userResponse->assertStatus(201)
            ->assertJsonPath('user.name', 'Alex Inventory');

        $newUser = User::where('email', $userEmail)->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->hasRole($roleName));
        $this->assertTrue($newUser->hasPermissionTo('products.view'));
        $this->assertFalse($newUser->hasPermissionTo('finance.view'));
    }

    public function test_unauthenticated_request_to_me_returns_401(): void
    {
        $response = $this->getJson('/api/me');
        $response->assertStatus(401);
    }
}

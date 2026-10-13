<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    public function test_super_admin_receives_full_dashboard_including_revenue_metrics(): void
    {
        $admin = User::where('email', 'admin@shop.test')->firstOrFail();
        Sanctum::actingAs($admin);

        $res = $this->getJson('/api/admin/dashboard');

        $res->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'cards' => [
                        'todays_orders',
                        'new_customers',
                        'low_stock_products',
                        'open_chats',
                        'pending_reviews',
                    ],
                    'revenue' => [
                        'today_revenue',
                        'month_revenue',
                        'total_revenue',
                        'avg_order_value',
                    ],
                    'charts' => [
                        'sales_last_30_days',
                        'top_products',
                        'orders_by_status',
                    ],
                    'latest_orders',
                    'low_stock_items',
                    'notifications' => [
                        'unread_count',
                        'items',
                    ],
                ],
            ]);

        $this->assertNotNull($res->json('data.revenue'));
        $this->assertCount(30, $res->json('data.charts.sales_last_30_days'));
        $this->assertArrayHasKey('revenue', $res->json('data.charts.sales_last_30_days.0'));
    }

    public function test_product_manager_receives_operational_dashboard_without_revenue_data(): void
    {
        $pm = User::where('email', 'product@shop.test')->firstOrFail();
        Sanctum::actingAs($pm);

        $res = $this->getJson('/api/admin/dashboard');

        $res->assertOk()
            ->assertJsonPath('data.revenue', null);

        // Ensure revenue is stripped from daily chart points & top products for non-finance roles
        $firstDay = $res->json('data.charts.sales_last_30_days.0');
        $this->assertArrayNotHasKey('revenue', $firstDay);

        $firstTopProduct = $res->json('data.charts.top_products.0');
        if ($firstTopProduct) {
            $this->assertArrayNotHasKey('revenue', $firstTopProduct);
        }
    }

    public function test_customer_cannot_access_admin_dashboard_endpoint(): void
    {
        $customer = User::where('email', 'customer@shop.test')->firstOrFail();
        Sanctum::actingAs($customer);

        $res = $this->getJson('/api/admin/dashboard');
        $res->assertStatus(403);
    }
}

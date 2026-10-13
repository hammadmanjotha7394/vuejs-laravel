<?php

namespace Tests\Feature;

use App\Models\EmailLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Tests\TestCase;

class AdminOrderCustomerFinanceTest extends TestCase
{
    public function test_changing_order_to_shipped_sends_shipped_email_and_logs_timeline(): void
    {
        $om = User::where('email', 'order@shop.test')->first();
        $this->assertNotNull($om);

        $order = Order::first();
        $this->assertNotNull($order);

        $originalStatus = $order->status;
        $originalTracking = $order->tracking_number;

        // 1. Order manager can list orders with filters
        $listResponse = $this->actingAs($om)->getJson('/api/admin/orders');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'summary' => ['total_orders', 'unfulfilled_count', 'shipped_count', 'refunded_count', 'low_stock_count'],
            ]);

        // 2. Order manager can view order details
        $showResponse = $this->actingAs($om)->getJson("/api/admin/orders/{$order->id}");
        $showResponse->assertStatus(200)
            ->assertJsonPath('data.id', $order->id);

        // 3. Changing status to shipped sends order_shipped email and records status history
        $updateResponse = $this->actingAs($om)
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'shipped',
                'note' => 'Dispatched via DHL Express.',
                'tracking_number' => 'DHL-992817263',
                'tracking_url' => 'https://www.dhl.com/en/express/tracking.html?AWB=DHL-992817263',
                'notify_customer' => true,
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'shipped')
            ->assertJsonPath('data.tracking_number', 'DHL-992817263');

        $recipientEmail = $order->guest_email ?: $order->user?->email;
        $this->assertDatabaseHas('email_logs', [
            'to' => $recipientEmail,
            'template_key' => 'order_shipped',
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'status' => 'shipped',
            'note' => 'Dispatched via DHL Express.',
        ]);

        // Restore original order state
        $order->status = $originalStatus;
        $order->tracking_number = $originalTracking;
        $order->save();
    }

    public function test_order_manager_can_manage_orders_and_customers_but_gets_403_on_finance_and_refund(): void
    {
        $om = User::where('email', 'order@shop.test')->first();
        $customer = User::where('email', 'customer@shop.test')->first();
        $order = Order::first();

        // 1. Order manager can add internal notes & resend confirmation email
        $this->actingAs($om)
            ->postJson("/api/admin/orders/{$order->id}/notes", [
                'note' => 'Customer requested discreet packaging.',
                'admin_notes' => 'VIP repeat buyer.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.admin_notes', 'VIP repeat buyer.');

        $this->actingAs($om)
            ->postJson("/api/admin/orders/{$order->id}/resend-confirmation")
            ->assertStatus(200);

        // 2. Order manager can list and view customers, update customer notes, and export CSV
        $this->actingAs($om)
            ->getJson('/api/admin/customers')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);

        $this->actingAs($om)
            ->getJson("/api/admin/customers/{$customer->id}")
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'orders_count', 'total_spent', 'orders', 'addresses'],
            ]);

        $this->actingAs($om)
            ->putJson("/api/admin/customers/{$customer->id}", [
                'admin_notes' => 'Prefers express delivery.',
                'is_active' => true,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.admin_notes', 'Prefers express delivery.');

        $this->actingAs($om)
            ->get('/api/admin/customers/export-csv')
            ->assertStatus(200);

        // 3. Order manager receives 403 on Finance endpoints and Refund endpoint
        $this->actingAs($om)
            ->getJson('/api/admin/finance/summary')
            ->assertStatus(403);

        $this->actingAs($om)
            ->get('/api/admin/finance/export-csv')
            ->assertStatus(403);

        $this->actingAs($om)
            ->postJson("/api/admin/orders/{$order->id}/refund", ['amount' => 10.00])
            ->assertStatus(403);
    }

    public function test_super_admin_can_view_finance_analytics_by_day_week_month_and_export_csv(): void
    {
        $admin = User::where('email', 'admin@shop.test')->first();

        foreach (['day', 'week', 'month'] as $period) {
            $response = $this->actingAs($admin)
                ->getJson("/api/admin/finance/summary?period={$period}");

            $response->assertStatus(200)
                ->assertJsonPath('period', $period)
                ->assertJsonStructure([
                    'revenue_total',
                    'net_revenue',
                    'orders_count',
                    'average_order_value',
                    'refunds_total',
                    'refunds_count',
                    'tax_collected',
                    'shipping_collected',
                    'discount_given',
                    'currency',
                    'revenue_series',
                    'top_products',
                    'top_categories',
                    'stripe_balance' => ['available', 'pending', 'currency', 'mode'],
                    'recent_payouts',
                ]);
        }

        $csvResponse = $this->actingAs($admin)->get('/api/admin/finance/export-csv');
        $csvResponse->assertStatus(200);
    }

    public function test_inventory_low_stock_list_quick_edit_and_email_alert(): void
    {
        $om = User::where('email', 'order@shop.test')->first();
        $product = Product::first();
        $originalStock = $product->stock_qty;

        // 1. Fetch low-stock list
        $this->actingAs($om)
            ->getJson('/api/admin/inventory/low-stock')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'alert_email']);

        // 2. Quick stock update
        $this->actingAs($om)
            ->patchJson('/api/admin/inventory/stock', [
                'product_id' => $product->id,
                'stock_qty' => 25,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.stock_qty', 25);

        // Restore original stock
        $product->stock_qty = $originalStock;
        $product->save();

        // 3. Send low-stock alert email
        $alertEmail = 'inventory-alerts@shop.test';
        $this->actingAs($om)
            ->postJson('/api/admin/inventory/low-stock-alert', [
                'email' => $alertEmail,
            ])
            ->assertStatus(200)
            ->assertJsonPath('recipient', $alertEmail);

        $this->assertDatabaseHas('email_logs', [
            'to' => $alertEmail,
            'template_key' => 'low_stock_alert',
            'status' => 'sent',
        ]);

        EmailLog::where('to', $alertEmail)->delete();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    protected array $createdUserIds = [];

    protected array $createdOrderIds = [];

    protected array $createdReviewIds = [];

    protected function tearDown(): void
    {
        if (! empty($this->createdReviewIds)) {
            Review::whereIn('id', $this->createdReviewIds)->delete();
        }
        if (! empty($this->createdOrderIds)) {
            Order::whereIn('id', $this->createdOrderIds)->delete();
        }
        if (! empty($this->createdUserIds)) {
            Address::whereIn('user_id', $this->createdUserIds)->delete();
            User::withTrashed()->whereIn('id', $this->createdUserIds)->forceDelete();
        }

        parent::tearDown();
    }

    protected function createTestCustomer(string $suffix): User
    {
        $user = User::create([
            'name' => "Customer {$suffix}",
            'email' => "customer.{$suffix}.".uniqid().'@example.com',
            'password' => Hash::make('SecretPass123!'),
            'is_active' => true,
        ]);
        $user->assignRole('Customer');
        $this->createdUserIds[] = $user->id;

        return $user;
    }

    protected function createTestOrder(User $user, string $status = 'paid', string $paymentStatus = 'paid'): Order
    {
        $product = Product::where('status', 'active')->firstOrFail();

        $order = Order::create([
            'order_number' => 'ORD-TEST-'.strtoupper(uniqid()),
            'user_id' => $user->id,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'subtotal' => 85.00,
            'discount_total' => 0,
            'shipping_total' => 10.00,
            'tax_total' => 6.80,
            'grand_total' => 101.80,
            'currency' => 'USD',
            'shipping_address_json' => [
                'name' => $user->name,
                'email' => $user->email,
                'line1' => '100 Main St',
                'city' => 'Austin',
                'state' => 'TX',
                'postal_code' => '78701',
                'country' => 'US',
            ],
            'billing_address_json' => [
                'name' => $user->name,
                'email' => $user->email,
                'line1' => '100 Main St',
                'city' => 'Austin',
                'state' => 'TX',
                'postal_code' => '78701',
                'country' => 'US',
            ],
            'placed_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku ?: 'SKU-1',
            'unit_price' => 85.00,
            'qty' => 1,
            'line_total' => 85.00,
        ]);

        $this->createdOrderIds[] = $order->id;

        return $order;
    }

    public function test_customer_can_view_own_overview_and_orders_and_never_see_another_users_order(): void
    {
        $customerA = $this->createTestCustomer('Alice');
        $customerB = $this->createTestCustomer('Bob');

        $orderA = $this->createTestOrder($customerA, 'paid', 'paid');
        $orderB = $this->createTestOrder($customerB, 'processing', 'paid');

        Sanctum::actingAs($customerA);

        // Overview shows only Alice's counts
        $overviewRes = $this->getJson('/api/account/overview');
        $overviewRes->assertOk()
            ->assertJsonPath('data.stats.total_orders', 1)
            ->assertJsonPath('data.recent_orders.0.order_number', $orderA->order_number);

        // Alice can view her own order details
        $ownOrderRes = $this->getJson("/api/account/orders/{$orderA->order_number}");
        $ownOrderRes->assertOk()
            ->assertJsonPath('data.order_number', $orderA->order_number)
            ->assertJsonPath('data.grand_total', 101.8);

        // Changing the order number in the URL to Bob's order returns 404 and reveals nothing
        $otherOrderRes = $this->getJson("/api/account/orders/{$orderB->order_number}");
        $otherOrderRes->assertStatus(404);

        // Attempting to cancel Bob's order also returns 404
        $cancelOtherRes = $this->postJson("/api/account/orders/{$orderB->order_number}/cancel-request", [
            'reason' => 'Trying to cancel someone else order',
        ]);
        $cancelOtherRes->assertStatus(404);
    }

    public function test_customer_can_request_cancellation_for_unshipped_orders(): void
    {
        $customer = $this->createTestCustomer('CancelTester');
        $paidOrder = $this->createTestOrder($customer, 'paid', 'paid');
        $shippedOrder = $this->createTestOrder($customer, 'shipped', 'paid');

        Sanctum::actingAs($customer);

        // Request cancellation on paid unshipped order succeeds
        $res = $this->postJson("/api/account/orders/{$paidOrder->order_number}/cancel-request", [
            'reason' => 'Ordered the wrong size by mistake.',
        ]);

        $res->assertOk()
            ->assertJsonPath('data.has_cancel_request', true)
            ->assertJsonPath('data.can_request_cancel', false);

        // Requesting cancellation on a shipped order fails with 422
        $shippedRes = $this->postJson("/api/account/orders/{$shippedOrder->order_number}/cancel-request", [
            'reason' => 'Too late to cancel',
        ]);
        $shippedRes->assertStatus(422);
    }

    public function test_customer_can_manage_saved_addresses_with_strict_user_isolation(): void
    {
        $customerA = $this->createTestCustomer('AddrAlice');
        $customerB = $this->createTestCustomer('AddrBob');

        Sanctum::actingAs($customerA);

        // Create first address (auto-defaults)
        $storeRes1 = $this->postJson('/api/account/addresses', [
            'label' => 'Home',
            'name' => 'Alice Smith',
            'phone' => '555-0101',
            'line1' => '123 Maple Ave',
            'city' => 'Seattle',
            'state' => 'WA',
            'postal_code' => '98101',
            'country' => 'US',
        ]);
        $storeRes1->assertCreated()
            ->assertJsonPath('data.is_default_shipping', true);

        $addr1Id = $storeRes1->json('data.id');

        // Create second address and set as default
        $storeRes2 = $this->postJson('/api/account/addresses', [
            'label' => 'Office',
            'name' => 'Alice Smith',
            'line1' => '456 Pine St',
            'city' => 'Seattle',
            'state' => 'WA',
            'postal_code' => '98102',
            'country' => 'US',
            'is_default_shipping' => false,
        ]);
        $storeRes2->assertCreated();
        $addr2Id = $storeRes2->json('data.id');

        $defaultRes = $this->postJson("/api/account/addresses/{$addr2Id}/default");
        $defaultRes->assertOk();

        $this->assertDatabaseHas('addresses', ['id' => $addr2Id, 'is_default_shipping' => true]);
        $this->assertDatabaseHas('addresses', ['id' => $addr1Id, 'is_default_shipping' => false]);

        // Bob cannot modify or delete Alice's address
        Sanctum::actingAs($customerB);
        $this->deleteJson("/api/account/addresses/{$addr1Id}")->assertStatus(404);
    }

    public function test_customer_can_permanently_delete_account_with_password_confirmation(): void
    {
        $customer = $this->createTestCustomer('DeleteMe');
        $order = $this->createTestOrder($customer, 'paid', 'paid');

        Sanctum::actingAs($customer);

        // Wrong password fails
        $badRes = $this->deleteJson('/api/account', [
            'password' => 'WrongPassword!',
        ]);
        $badRes->assertStatus(422);

        // Correct password deletes account while preserving order record (user_id becomes null)
        $okRes = $this->deleteJson('/api/account', [
            'password' => 'SecretPass123!',
        ]);
        $okRes->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => null,
        ]);
    }
}

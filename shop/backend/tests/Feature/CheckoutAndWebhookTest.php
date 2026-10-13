<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CheckoutAndWebhookTest extends TestCase
{
    protected function tearDown(): void
    {
        Cart::query()->delete();
        parent::tearDown();
    }

    public function test_checkout_creates_pending_order_with_snapshots_and_does_not_deduct_stock_until_webhook(): void
    {
        $product = Product::where('track_stock', true)
            ->where('stock_qty', '>=', 10)
            ->whereIn('status', ['active', 'published'])
            ->first();

        $this->assertNotNull($product);
        $initialStock = (int) $product->stock_qty;

        // 1. Add 2 items to guest cart
        $cartRes = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $cartRes->assertStatus(201);
        $token = $cartRes->json('data.token');

        $shippingRate = ShippingRate::where('is_active', true)->first();

        // 2. Submit checkout
        $checkoutRes = $this->withHeader('X-Cart-Token', $token)
            ->postJson('/api/checkout', [
                'email' => 'buyer@example.com',
                'shipping_rate_id' => $shippingRate?->id,
                'shipping_address' => [
                    'first_name' => 'Clara',
                    'last_name' => 'Oswald',
                    'line1' => '742 Evergreen Terrace',
                    'city' => 'Springfield',
                    'state' => 'IL',
                    'postal_code' => '62704',
                    'country' => 'US',
                    'phone' => '+15550192834',
                ],
                'notes' => 'Leave at front porch.',
            ]);

        $checkoutRes->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'order_id',
                    'order_number',
                    'session_id',
                    'checkout_url',
                    'grand_total',
                ],
            ]);

        $orderId = $checkoutRes->json('data.order_id');
        $orderNumber = $checkoutRes->json('data.order_number');
        $sessionId = $checkoutRes->json('data.session_id');

        // Verify order is PENDING and stock is UNCHANGED before webhook
        $order = Order::with('items')->findOrFail($orderId);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals('pending', $order->payment_status);
        $this->assertCount(1, $order->items);
        $this->assertEquals($product->name, $order->items->first()->name_snapshot);
        $this->assertEquals(2, $order->items->first()->qty);

        $product->refresh();
        $this->assertEquals($initialStock, (int) $product->stock_qty);
    }

    protected function postStripeWebhook(array $payload): TestResponse
    {
        $webhookSecret = (string) SettingService::get('stripe_webhook_secret', '');
        $rawPayload = json_encode($payload);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$rawPayload}", $webhookSecret);

        return $this->call(
            'POST',
            '/api/stripe/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            ],
            $rawPayload
        );
    }

    public function test_webhook_rejects_missing_or_invalid_signature(): void
    {
        $res = $this->postJson('/api/stripe/webhook', [
            'id' => 'evt_forged',
            'type' => 'checkout.session.completed',
        ], [
            'Stripe-Signature' => 't=12345,v1=forged_invalid_signature',
        ]);

        $res->assertStatus(400);
    }

    public function test_webhook_marks_order_paid_deducts_stock_once_clears_cart_and_is_idempotent(): void
    {
        $product = Product::where('track_stock', true)
            ->where('stock_qty', '>=', 10)
            ->whereIn('status', ['active', 'published'])
            ->first();

        $initialStock = (int) $product->stock_qty;

        // Add 2 items to cart
        $cartRes = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $token = $cartRes->json('data.token');
        $cartId = $cartRes->json('data.id');

        // Create checkout session
        $checkoutRes = $this->withHeader('X-Cart-Token', $token)
            ->postJson('/api/checkout', [
                'email' => 'webhook-buyer@example.com',
                'shipping_address' => [
                    'first_name' => 'Arthur',
                    'last_name' => 'Pendelton',
                    'line1' => '100 Market St',
                    'city' => 'San Francisco',
                    'state' => 'CA',
                    'postal_code' => '94105',
                    'country' => 'US',
                ],
            ]);

        $orderId = $checkoutRes->json('data.order_id');
        $orderNumber = $checkoutRes->json('data.order_number');
        $sessionId = $checkoutRes->json('data.session_id');

        $webhookPayload = [
            'id' => 'evt_test_1001',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => $sessionId,
                    'object' => 'checkout.session',
                    'payment_intent' => 'pi_test_998877',
                    'metadata' => [
                        'order_id' => (string) $orderId,
                        'order_number' => $orderNumber,
                        'cart_id' => (string) $cartId,
                    ],
                ],
            ],
        ];

        // 1. First webhook delivery (signed with valid Stripe-Signature)
        $webhookRes1 = $this->postStripeWebhook($webhookPayload);
        $webhookRes1->assertStatus(200);

        $order = Order::findOrFail($orderId);
        $this->assertEquals('paid', $order->status);
        $this->assertEquals('paid', $order->payment_status);

        // Stock decreased by 2
        $product->refresh();
        $this->assertEquals($initialStock - 2, (int) $product->stock_qty);

        // Cart was cleared
        $cartCheck = $this->withHeader('X-Cart-Token', $token)->getJson('/api/cart');
        $cartCheck->assertJsonPath('data.item_count', 0);

        // Email log recorded
        $this->assertTrue(
            EmailLog::where('to', 'webhook-buyer@example.com')
                ->where('template_key', 'order_confirmed')
                ->exists()
        );

        // 2. Duplicate webhook delivery (Idempotency Check)
        $webhookRes2 = $this->postStripeWebhook($webhookPayload);
        $webhookRes2->assertStatus(200);

        // Stock must NOT decrease a second time!
        $product->refresh();
        $this->assertEquals($initialStock - 2, (int) $product->stock_qty);

        // Restore product stock after test
        $product->stock_qty = $initialStock;
        $product->save();
    }

    public function test_expired_checkout_session_marks_order_cancelled_and_leaves_cart_intact(): void
    {
        $product = Product::whereIn('status', ['active', 'published'])->first();

        $cartRes = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
        $token = $cartRes->json('data.token');

        $checkoutRes = $this->withHeader('X-Cart-Token', $token)
            ->postJson('/api/checkout', [
                'email' => 'expired@example.com',
                'shipping_address' => [
                    'first_name' => 'Jane',
                    'last_name' => 'Eyre',
                    'line1' => '22 Baker St',
                    'city' => 'Boston',
                    'state' => 'MA',
                    'postal_code' => '02108',
                    'country' => 'US',
                ],
            ]);

        $orderId = $checkoutRes->json('data.order_id');
        $sessionId = $checkoutRes->json('data.session_id');

        // Send checkout.session.expired webhook signed with Stripe-Signature
        $this->postStripeWebhook([
            'id' => 'evt_test_expired',
            'object' => 'event',
            'type' => 'checkout.session.expired',
            'data' => [
                'object' => [
                    'id' => $sessionId,
                    'object' => 'checkout.session',
                    'metadata' => [
                        'order_id' => (string) $orderId,
                    ],
                ],
            ],
        ])->assertStatus(200);

        $order = Order::findOrFail($orderId);
        $this->assertEquals('cancelled', $order->status);
        $this->assertEquals('expired', $order->payment_status);

        // Cart still has all 3 items intact!
        $cartCheck = $this->withHeader('X-Cart-Token', $token)->getJson('/api/cart');
        $cartCheck->assertJsonPath('data.item_count', 3);
    }

    public function test_success_page_verification_requires_matching_session_id(): void
    {
        $product = Product::whereIn('status', ['active', 'published'])->first();

        $cartRes = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $token = $cartRes->json('data.token');

        $checkoutRes = $this->withHeader('X-Cart-Token', $token)
            ->postJson('/api/checkout', [
                'email' => 'verify@example.com',
                'shipping_address' => [
                    'first_name' => 'Miles',
                    'last_name' => 'Vorkosigan',
                    'line1' => '500 Imperial Ave',
                    'city' => 'Austin',
                    'state' => 'TX',
                    'postal_code' => '78701',
                    'country' => 'US',
                ],
            ]);

        $orderNumber = $checkoutRes->json('data.order_number');
        $sessionId = $checkoutRes->json('data.session_id');

        // 1. Mismatched session_id must return 404
        $badVerify = $this->getJson("/api/checkout/verify?order={$orderNumber}&session_id=cs_wrong_session_id");
        $badVerify->assertStatus(404);

        // 2. Valid session_id + order_number returns 200 with order details
        $goodVerify = $this->getJson("/api/checkout/verify?order={$orderNumber}&session_id={$sessionId}");
        $goodVerify->assertStatus(200)
            ->assertJsonPath('data.order_number', $orderNumber);
    }

    public function test_admin_can_issue_refund_and_unauthorized_staff_cannot(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST-REFUND1',
            'guest_email' => 'refund-customer@example.com',
            'status' => 'paid',
            'payment_status' => 'paid',
            'subtotal' => 100.00,
            'discount_total' => 0.00,
            'shipping_total' => 0.00,
            'tax_total' => 0.00,
            'grand_total' => 100.00,
            'currency' => 'USD',
            'shipping_address_json' => ['first_name' => 'Test', 'last_name' => 'User', 'line1' => '123 St', 'city' => 'NY', 'postal_code' => '10001', 'country' => 'US'],
            'billing_address_json' => ['first_name' => 'Test', 'last_name' => 'User', 'line1' => '123 St', 'city' => 'NY', 'postal_code' => '10001', 'country' => 'US'],
            'stripe_payment_intent_id' => 'pi_simulated_refund',
            'placed_at' => now(),
        ]);

        // Product manager lacks orders.refund permission
        $productManager = User::where('email', 'product@shop.test')->first();
        $this->actingAs($productManager, 'sanctum')
            ->postJson("/api/admin/orders/{$order->id}/refund", [
                'amount' => 40.00,
                'reason' => 'Customer requested partial refund',
            ])
            ->assertStatus(403);

        // Super admin has permission
        $admin = User::where('email', 'admin@shop.test')->first();
        $refundRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/orders/{$order->id}/refund", [
                'amount' => 40.00,
                'reason' => 'Customer requested partial refund',
            ]);

        $refundRes->assertStatus(200)
            ->assertJsonPath('data.status', 'refunded')
            ->assertJsonPath('data.payment_status', 'partially_refunded');

        $order->delete();
    }
}

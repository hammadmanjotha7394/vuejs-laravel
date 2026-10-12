<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Tests\TestCase;

class CartAndWishlistTest extends TestCase
{
    protected function tearDown(): void
    {
        Cart::query()->delete();
        Wishlist::query()->delete();
        parent::tearDown();
    }

    public function test_guest_can_add_item_to_cart_and_receives_cart_token(): void
    {
        $product = Product::whereIn('status', ['active', 'published'])->first();
        $this->assertNotNull($product);

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.item_count', 2)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'items',
                    'item_count',
                    'subtotal',
                    'shipping',
                    'grand_total',
                ],
            ]);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        // Subsequent GET request with X-Cart-Token header
        $getRes = $this->withHeader('X-Cart-Token', $token)->getJson('/api/cart');
        $getRes->assertStatus(200)
            ->assertJsonPath('data.item_count', 2)
            ->assertJsonPath('data.items.0.product_id', $product->id);
    }

    public function test_server_is_absolute_source_of_truth_for_prices(): void
    {
        $product = Product::whereIn('status', ['active', 'published'])->first();

        // Client attempts to pass a fake price of $1.00
        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 1.00,
        ]);

        $response->assertStatus(201);
        $expectedPrice = (float) $product->base_price;
        $this->assertEquals($expectedPrice, (float) $response->json('data.items.0.unit_price'));
        $this->assertEquals($expectedPrice, (float) $response->json('data.subtotal'));
    }

    public function test_cart_item_update_and_removal(): void
    {
        $product = Product::whereIn('status', ['active', 'published'])->first();

        $addRes = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $token = $addRes->json('data.token');
        $itemId = $addRes->json('data.items.0.id');

        // Update quantity to 3
        $updateRes = $this->withHeader('X-Cart-Token', $token)
            ->putJson("/api/cart/items/{$itemId}", [
                'quantity' => 3,
            ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.item_count', 3);

        // Delete item
        $deleteRes = $this->withHeader('X-Cart-Token', $token)
            ->deleteJson("/api/cart/items/{$itemId}");

        $deleteRes->assertStatus(200)
            ->assertJsonPath('data.item_count', 0)
            ->assertJsonCount(0, 'data.items');
    }

    public function test_coupon_application_and_discount_calculation(): void
    {
        // WELCOME10 coupon: 10% off with min $50 subtotal
        $product = Product::where('base_price', '>=', 60)
            ->whereIn('status', ['active', 'published'])
            ->first();
        if (! $product) {
            $product = Product::whereIn('status', ['active', 'published'])->first();
        }

        $qty = (int) ceil(55 / (float) $product->base_price);

        $addRes = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => $qty,
        ]);

        $token = $addRes->json('data.token');
        $subtotal = (float) $addRes->json('data.subtotal');

        // Apply coupon
        $couponRes = $this->withHeader('X-Cart-Token', $token)
            ->postJson('/api/cart/coupon', [
                'code' => 'WELCOME10',
            ]);

        $couponRes->assertStatus(200)
            ->assertJsonPath('data.coupon.code', 'WELCOME10');

        $expectedDiscount = round(($subtotal * 10) / 100, 2);
        $this->assertEquals($expectedDiscount, (float) $couponRes->json('data.discount'));

        // Remove coupon
        $removeRes = $this->withHeader('X-Cart-Token', $token)
            ->deleteJson('/api/cart/coupon');

        $removeRes->assertStatus(200)
            ->assertJsonPath('data.coupon', null)
            ->assertJsonPath('data.discount', 0);
    }

    public function test_coupon_rejected_if_subtotal_below_minimum(): void
    {
        // Create cheap product for subtotal < $50
        $product = Product::where('base_price', '<', 40)->whereIn('status', ['active', 'published'])->first();
        if (! $product) {
            $product = Product::whereIn('status', ['active', 'published'])->first();
        }

        $addRes = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $token = $addRes->json('data.token');

        // Try applying SPRING15 which requires min $100
        $couponRes = $this->withHeader('X-Cart-Token', $token)
            ->postJson('/api/cart/coupon', [
                'code' => 'SPRING15',
            ]);

        $couponRes->assertStatus(422)
            ->assertJsonValidationErrors(['coupon']);
    }

    public function test_free_shipping_progress_and_unlock(): void
    {
        $product = Product::whereIn('status', ['active', 'published'])->first();

        // 1 item
        $res = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $token = $res->json('data.token');
        $subtotal = (float) $res->json('data.subtotal');

        if ($subtotal < 75.0) {
            $this->assertFalse($res->json('data.shipping.is_free'));
            $this->assertGreaterThan(0, $res->json('data.shipping.amount_needed'));
        }

        // Add enough items to exceed $75
        $neededQty = (int) ceil(80 / (float) $product->base_price);
        $res2 = $this->withHeader('X-Cart-Token', $token)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => $neededQty,
            ]);

        $res2->assertStatus(201)
            ->assertJsonPath('data.shipping.is_free', true)
            ->assertJsonPath('data.shipping.amount_needed', 0)
            ->assertJsonPath('data.shipping.progress_percentage', 100);
    }

    public function test_guest_cart_merged_into_user_cart_on_login(): void
    {
        $user = User::role('customer')->first() ?: User::first();
        $product1 = Product::whereIn('status', ['active', 'published'])->first();
        $product2 = Product::whereIn('status', ['active', 'published'])->skip(1)->first();

        // Guest adds product1
        $guestRes = $this->postJson('/api/cart/items', [
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);
        $guestToken = $guestRes->json('data.token');

        // User already has product2 in their cart
        $userCart = Cart::create(['user_id' => $user->id, 'token' => 'user-cart-token']);
        $userCart->items()->create(['product_id' => $product2->id, 'quantity' => 1]);

        // Merge guest cart into user
        $mergeRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart/merge', [
                'guest_token' => $guestToken,
            ]);

        $mergeRes->assertStatus(200)
            ->assertJsonPath('data.item_count', 3);

        $mergedIds = collect($mergeRes->json('data.items'))->pluck('product_id')->all();
        $this->assertContains($product1->id, $mergedIds);
        $this->assertContains($product2->id, $mergedIds);
    }

    public function test_wishlist_toggle_and_sync(): void
    {
        $user = User::role('customer')->first() ?: User::first();
        $product = Product::whereIn('status', ['active', 'published'])->first();

        // 1. Toggle item (add)
        $toggleRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/wishlist/toggle', [
                'product_id' => $product->id,
            ]);

        $toggleRes->assertStatus(200)
            ->assertJsonPath('saved', true)
            ->assertJsonPath('count', 1);

        // 2. View wishlist
        $listRes = $this->actingAs($user, 'sanctum')->getJson('/api/wishlist');
        $listRes->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product->id);

        // 3. Toggle again (remove)
        $toggleRemoveRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/wishlist/toggle', [
                'product_id' => $product->id,
            ]);

        $toggleRemoveRes->assertStatus(200)
            ->assertJsonPath('saved', false)
            ->assertJsonPath('count', 0);

        // 4. Guest syncs array of products upon login
        $product2 = Product::whereIn('status', ['active', 'published'])->skip(1)->first();
        $syncRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/wishlist/sync', [
                'product_ids' => [$product->id, $product2->id],
            ]);

        $syncRes->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}

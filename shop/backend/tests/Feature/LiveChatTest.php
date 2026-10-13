<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ChatConversation;
use App\Models\EmailLog;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LiveChatTest extends TestCase
{
    use DatabaseTransactions;

    protected User $supportAgent;

    protected User $productManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->supportAgent = User::factory()->create(['is_active' => true, 'name' => 'Clara Support']);
        $this->supportAgent->assignRole('support_agent');

        $this->productManager = User::factory()->create(['is_active' => true]);
        $this->productManager->assignRole('product_manager');
    }

    public function test_visitor_and_admin_can_chat_via_polling_with_strict_visitor_isolation(): void
    {
        $tokenA = 'vis_token_alpha_123';
        $tokenB = 'vis_token_beta_456';

        // 1. Visitor A sends first message (with script tag that must be stripped)
        $sendRes = $this->postJson('/api/chat/messages', [
            'visitor_token' => $tokenA,
            'visitor_name' => 'Lucas Meyer',
            'visitor_email' => 'lucas@example.com',
            'current_page' => '/products/linen-shirt',
            'body' => 'Hello! Do you ship to Zurich?<script>alert(1)</script>',
        ]);

        $sendRes->assertStatus(201);
        $firstMsgId = $sendRes->json('data.id');
        $convId = $sendRes->json('conversation.id');

        $this->assertEquals('Hello! Do you ship to Zurich?alert(1)', $sendRes->json('data.body'));

        // 2. Visitor B polls with their own token -> sees empty conversation (cannot read Visitor A's chat)
        $this->getJson("/api/chat/messages?visitor_token={$tokenB}")
            ->assertStatus(200)
            ->assertJsonPath('conversation', null)
            ->assertJsonCount(0, 'messages');

        // 3. Support agent checks inbox -> sees Visitor A's conversation with unread_admin_count = 1
        $inboxRes = $this->actingAs($this->supportAgent)
            ->getJson('/api/admin/chat/conversations?filter=open');

        $inboxRes->assertStatus(200);
        $convRow = collect($inboxRes->json('data'))->firstWhere('id', $convId);
        $this->assertNotNull($convRow);
        $this->assertEquals(1, $convRow['unread_admin_count']);

        // 4. Support agent replies to Visitor A
        $replyRes = $this->actingAs($this->supportAgent)
            ->postJson("/api/admin/chat/conversations/{$convId}/messages", [
                'body' => 'Yes, we ship tracked express to Switzerland within 3 business days!',
            ]);

        $replyRes->assertStatus(201);
        $replyMsgId = $replyRes->json('data.id');

        // 5. Visitor A polls with since_id=$firstMsgId -> receives ONLY the new admin reply
        $pollRes = $this->getJson("/api/chat/messages?visitor_token={$tokenA}&since_id={$firstMsgId}&panel_open=1");
        $pollRes->assertStatus(200)
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.id', $replyMsgId)
            ->assertJsonPath('messages.0.sender_type', 'admin')
            ->assertJsonPath('unread_count', 0);
    }

    public function test_admin_can_view_visitor_cart_context_and_offline_visitor_receives_email_reply(): void
    {
        Mail::fake();

        $token = 'vis_offline_cart_999';
        $product = Product::create([
            'name' => 'Brass Table Lamp',
            'slug' => 'brass-table-lamp',
            'sku' => 'LAMP-01',
            'base_price' => 180.00,
            'status' => 'active',
        ]);

        $cart = Cart::create(['token' => $token]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Create conversation where visitor was last seen 5 minutes ago (offline)
        $conversation = ChatConversation::create([
            'visitor_token' => $token,
            'visitor_name' => 'Amelie Poulain',
            'visitor_email' => 'amelie@example.com',
            'current_page' => '/cart',
            'status' => 'open',
            'last_message_at' => now()->subMinutes(5),
            'visitor_last_seen_at' => now()->subMinutes(5),
            'unread_admin_count' => 1,
        ]);

        // Support agent opens conversation and sees visitor's cart context (2x Brass Table Lamp = $360)
        $detailRes = $this->actingAs($this->supportAgent)
            ->getJson("/api/admin/chat/conversations/{$conversation->id}");

        $detailRes->assertStatus(200)
            ->assertJsonPath('data.visitor_context.cart_subtotal', 360)
            ->assertJsonPath('data.visitor_context.cart_items.0.name', 'Brass Table Lamp');

        // Support agent replies while visitor is offline -> email is sent and logged
        $replyRes = $this->actingAs($this->supportAgent)
            ->postJson("/api/admin/chat/conversations/{$conversation->id}/messages", [
                'body' => 'Let us know if you need a 220V EU plug adapter for your Brass Table Lamp!',
            ]);

        $replyRes->assertStatus(201)
            ->assertJsonPath('emailed_offline_visitor', true);

        $this->assertTrue(
            EmailLog::where('to', 'amelie@example.com')
                ->where('template_key', 'chat_offline_reply')
                ->exists()
        );

        // Product manager without chat.manage gets 403
        $this->actingAs($this->productManager)
            ->getJson('/api/admin/chat/conversations')
            ->assertStatus(403);
    }
}

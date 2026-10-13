<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Models\User;
use App\Services\EmailService;
use App\Services\ImageService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ChatController extends Controller
{
    /**
     * List conversations for the Admin Chat Inbox (unread first, filter open/closed/mine, search).
     */
    public function index(Request $request): JsonResponse
    {
        // Record admin online heartbeat (expires after 2 minutes of inactivity)
        Cache::put('chat_admin_last_seen_at', now()->timestamp, 120);

        $query = ChatConversation::query()
            ->with(['assignedAdmin:id,name']);

        $filter = $request->input('filter', 'open');
        if ($filter === 'open') {
            $query->where('status', 'open');
        } elseif ($filter === 'closed') {
            $query->where('status', 'closed');
        } elseif ($filter === 'mine' && $request->user()) {
            $query->where('assigned_admin_id', $request->user()->id);
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('visitor_name', 'like', "%{$term}%")
                    ->orWhere('visitor_email', 'like', "%{$term}%")
                    ->orWhereHas('messages', fn ($mq) => $mq->where('body', 'like', "%{$term}%"));
            });
        }

        $conversations = $query
            ->orderByDesc('unread_admin_count')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        // Attach last message preview
        $convIds = $conversations->pluck('id')->all();
        $lastMessages = ChatMessage::whereIn('conversation_id', $convIds)
            ->latest('id')
            ->get()
            ->unique('conversation_id')
            ->keyBy('conversation_id');

        $formatted = $conversations->map(function (ChatConversation $conv) use ($lastMessages) {
            $lastMsg = $lastMessages->get($conv->id);
            $isVisitorOnline = $conv->visitor_last_seen_at && $conv->visitor_last_seen_at->gt(now()->subSeconds(45));

            return [
                'id' => $conv->id,
                'visitor_name' => $conv->visitor_name ?: 'Guest Visitor',
                'visitor_email' => $conv->visitor_email,
                'current_page' => $conv->current_page ?: '/',
                'status' => $conv->status,
                'assigned_admin_id' => $conv->assigned_admin_id,
                'assigned_admin' => $conv->assignedAdmin ? [
                    'id' => $conv->assignedAdmin->id,
                    'name' => $conv->assignedAdmin->name,
                ] : null,
                'unread_admin_count' => (int) $conv->unread_admin_count,
                'last_message_at' => $conv->last_message_at?->toIso8601String(),
                'is_visitor_online' => (bool) $isVisitorOnline,
                'last_message' => $lastMsg ? [
                    'body' => $lastMsg->body,
                    'sender_type' => $lastMsg->sender_type,
                    'created_at' => $lastMsg->created_at?->toIso8601String(),
                ] : null,
            ];
        });

        $staffList = User::whereHas('roles', fn ($q) => $q->where('name', '!=', 'customer'))
            ->where('is_active', true)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'data' => $formatted,
            'unread_total' => (int) ChatConversation::where('status', 'open')->sum('unread_admin_count'),
            'staff' => $staffList,
        ]);
    }

    /**
     * Show a single conversation with messages and rich visitor context (cart, past orders).
     */
    public function show(Request $request, ChatConversation $conversation): JsonResponse
    {
        Cache::put('chat_admin_last_seen_at', now()->timestamp, 120);

        $sinceId = max(0, (int) $request->input('since_id', 0));

        if ($conversation->unread_admin_count > 0) {
            $conversation->update(['unread_admin_count' => 0]);
            ChatMessage::where('conversation_id', $conversation->id)
                ->where('sender_type', 'visitor')
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        $msgQuery = ChatMessage::where('conversation_id', $conversation->id)
            ->with('sender:id,name')
            ->orderBy('id');

        if ($sinceId > 0) {
            $msgQuery->where('id', '>', $sinceId);
        }

        $messages = $msgQuery->get();

        // Build visitor context (active cart contents & past orders)
        $cartItems = [];
        $cartSubtotal = 0.0;
        $cartQuery = Cart::query()->with(['items.product:id,name,slug,base_price', 'items.variant:id,sku,price']);
        if ($conversation->user_id) {
            $cart = $cartQuery->where('user_id', $conversation->user_id)->latest('id')->first();
        } else {
            $cart = $cartQuery->where('token', $conversation->visitor_token)->latest('id')->first();
        }

        if ($cart && $cart->items) {
            foreach ($cart->items as $ci) {
                $unitPrice = (float) ($ci->variant?->price ?? $ci->product?->base_price ?? 0);
                $lineTotal = $unitPrice * (int) $ci->quantity;
                $cartSubtotal += $lineTotal;
                $cartItems[] = [
                    'id' => $ci->id,
                    'name' => $ci->product?->name ?? 'Product',
                    'slug' => $ci->product?->slug,
                    'quantity' => (int) $ci->quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => round($lineTotal, 2),
                ];
            }
        }

        $pastOrders = [];
        if ($conversation->user_id || $conversation->visitor_email) {
            $orderQuery = Order::query();
            if ($conversation->user_id && $conversation->visitor_email) {
                $orderQuery->where(function ($q) use ($conversation) {
                    $q->where('user_id', $conversation->user_id)
                        ->orWhere('guest_email', $conversation->visitor_email);
                });
            } elseif ($conversation->user_id) {
                $orderQuery->where('user_id', $conversation->user_id);
            } else {
                $orderQuery->where('guest_email', $conversation->visitor_email);
            }

            $pastOrders = $orderQuery->latest('id')
                ->take(5)
                ->get(['id', 'order_number', 'status', 'payment_status', 'grand_total', 'created_at']);
        }

        $isVisitorOnline = $conversation->visitor_last_seen_at && $conversation->visitor_last_seen_at->gt(now()->subSeconds(45));

        return response()->json([
            'data' => [
                'conversation' => [
                    'id' => $conversation->id,
                    'visitor_token' => $conversation->visitor_token,
                    'user_id' => $conversation->user_id,
                    'visitor_name' => $conversation->visitor_name ?: 'Guest Visitor',
                    'visitor_email' => $conversation->visitor_email,
                    'current_page' => $conversation->current_page ?: '/',
                    'status' => $conversation->status,
                    'assigned_admin_id' => $conversation->assigned_admin_id,
                    'internal_notes' => $conversation->internal_notes,
                    'last_message_at' => $conversation->last_message_at?->toIso8601String(),
                    'is_visitor_online' => (bool) $isVisitorOnline,
                ],
                'messages' => $messages,
                'visitor_context' => [
                    'cart_items' => $cartItems,
                    'cart_subtotal' => round($cartSubtotal, 2),
                    'past_orders' => $pastOrders,
                ],
            ],
        ]);
    }

    /**
     * Send an admin reply message. If visitor left an email and is offline, send reply by email.
     */
    public function sendMessage(Request $request, ChatConversation $conversation): JsonResponse
    {
        Cache::put('chat_admin_last_seen_at', now()->timestamp, 120);

        $validated = $request->validate([
            'body' => 'nullable|string|max:2000',
            'attachment_path' => 'nullable|string|max:500',
        ]);

        $cleanBody = isset($validated['body']) ? trim(strip_tags($validated['body'])) : '';
        $attachment = $validated['attachment_path'] ?? null;

        if ($cleanBody === '' && empty($attachment)) {
            return response()->json([
                'message' => 'Message body or attachment is required.',
            ], 422);
        }

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'admin',
            'sender_id' => $request->user()?->id,
            'body' => $cleanBody,
            'attachment_path' => $attachment,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'unread_visitor_count' => $conversation->unread_visitor_count + 1,
            'assigned_admin_id' => $conversation->assigned_admin_id ?: $request->user()?->id,
        ]);

        // Check if visitor has an email and is currently offline (> 45s since last poll)
        $emailedOfflineVisitor = false;
        $isVisitorOffline = ! $conversation->visitor_last_seen_at || $conversation->visitor_last_seen_at->lt(now()->subSeconds(45));

        if ($conversation->visitor_email && $isVisitorOffline && $cleanBody !== '') {
            $siteName = SettingService::get('site_name', 'Atelier Store');
            $subject = "New reply from {$siteName} Customer Care";
            $log = EmailService::sendTemplate(
                'chat_offline_reply',
                $conversation->visitor_email,
                [
                    'customer_name' => $conversation->visitor_name ?: 'Valued Customer',
                    'message_body' => e($cleanBody),
                ],
                $subject
            );
            $emailedOfflineVisitor = $log && $log->status === 'sent';
        }

        $message->load('sender:id,name');

        return response()->json([
            'message' => 'Reply sent.',
            'data' => $message,
            'emailed_offline_visitor' => $emailedOfflineVisitor,
        ], 201);
    }

    /**
     * Update conversation status (open/closed), assigned staff, or internal notes.
     */
    public function update(Request $request, ChatConversation $conversation): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|required|in:open,closed',
            'assigned_admin_id' => 'nullable|integer|exists:users,id',
            'internal_notes' => 'nullable|string|max:3000',
        ]);

        if (array_key_exists('internal_notes', $validated) && $validated['internal_notes'] !== null) {
            $validated['internal_notes'] = strip_tags($validated['internal_notes']);
        }

        $conversation->update($validated);

        return response()->json([
            'message' => 'Conversation updated.',
            'data' => $conversation->fresh(['assignedAdmin:id,name']),
        ]);
    }

    /**
     * Get canned quick replies for admin chat.
     */
    public function cannedResponses(): JsonResponse
    {
        $defaults = [
            [
                'title' => 'Greeting',
                'body' => 'Hello! Welcome to our studio. How can I help you with sizing, materials, or your order today?',
            ],
            [
                'title' => 'Shipping Timeline',
                'body' => 'In-stock pieces dispatch within 1–2 business days with complimentary carbon-neutral delivery on orders over $75.',
            ],
            [
                'title' => 'Return Policy',
                'body' => 'We offer a 30-day calm return and exchange window on all unworn items in original packaging.',
            ],
            [
                'title' => 'Order Lookup',
                'body' => 'Could you please share your order number (e.g. ORD-...) so I can check the live fulfillment status for you?',
            ],
        ];

        $saved = SettingService::getJson('chat_canned_responses', []);

        return response()->json([
            'data' => ! empty($saved) ? $saved : $defaults,
        ]);
    }

    /**
     * Update canned quick replies in admin.
     */
    public function updateCannedResponses(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'responses' => 'required|array|max:30',
            'responses.*.title' => 'required|string|max:80',
            'responses.*.body' => 'required|string|max:1000',
        ]);

        SettingService::setMany([
            'chat_canned_responses' => $validated['responses'],
        ], $request->user(), $request->ip());

        return response()->json([
            'message' => 'Canned responses updated.',
            'data' => SettingService::getJson('chat_canned_responses', []),
        ]);
    }

    /**
     * Upload an image attachment from the admin chat inbox.
     */
    public function uploadAttachment(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $processed = ImageService::processProductImage($request->file('image'), 'chat');

        return response()->json([
            'url' => $processed['medium_url'] ?: $processed['large_url'],
            'thumb_url' => $processed['thumb_url'],
        ]);
    }
}

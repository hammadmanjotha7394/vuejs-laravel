<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\ImageService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ChatController extends Controller
{
    /**
     * Resolve visitor token from header, query, body, or cookie.
     */
    protected function resolveVisitorToken(Request $request): ?string
    {
        $token = $request->header('X-Visitor-Token')
            ?: $request->input('visitor_token')
            ?: $request->cookie('boutique_visitor_token');

        if (! $token || ! is_string($token)) {
            return null;
        }

        return substr(preg_replace('/[^a-zA-Z0-9\-_]/', '', $token), 0, 100);
    }

    /**
     * Determine if live chat staff is currently online (recent admin heartbeat or within working hours).
     */
    protected function isStaffOnline(): bool
    {
        $lastAdminHeartbeat = (int) Cache::get('chat_admin_last_seen_at', 0);
        if ($lastAdminHeartbeat > 0 && (now()->timestamp - $lastAdminHeartbeat) <= 120) {
            return true;
        }

        return (bool) SettingService::get('chat_online_default', true);
    }

    /**
     * Cheap polling endpoint for visitor chat widget.
     * GET /api/chat/messages?since_id=0&visitor_token=...
     */
    public function index(Request $request): JsonResponse
    {
        $visitorToken = $this->resolveVisitorToken($request);
        $user = $request->user();

        if (! $visitorToken && ! $user) {
            return response()->json([
                'conversation' => null,
                'messages' => [],
                'unread_count' => 0,
                'is_online' => $this->isStaffOnline(),
                'greeting' => SettingService::get('chat_greeting', 'We usually reply within a few minutes.'),
                'offline_message' => SettingService::get('chat_offline_message', 'Our studio team is currently away. Leave your name and email and we will reply by email shortly.'),
            ]);
        }

        // Single indexed lookup scoped strictly to this visitor token (or user_id)
        $conversation = null;
        if ($visitorToken) {
            $conversation = ChatConversation::where('visitor_token', $visitorToken)->first();
        }
        if (! $conversation && $user) {
            $conversation = ChatConversation::where('user_id', $user->id)->latest('id')->first();
        }

        if (! $conversation) {
            return response()->json([
                'conversation' => null,
                'messages' => [],
                'unread_count' => 0,
                'is_online' => $this->isStaffOnline(),
                'greeting' => SettingService::get('chat_greeting', 'We usually reply within a few minutes.'),
                'offline_message' => SettingService::get('chat_offline_message', 'Our studio team is currently away. Leave your name and email and we will reply by email shortly.'),
            ]);
        }

        $sinceId = max(0, (int) $request->input('since_id', 0));
        $panelOpen = filter_var($request->input('panel_open', false), FILTER_VALIDATE_BOOLEAN);
        $currentPage = $request->input('current_page');

        // Lightweight heartbeat / unread reset only when needed
        $updates = ['visitor_last_seen_at' => now()];
        if ($currentPage && is_string($currentPage) && $currentPage !== $conversation->current_page) {
            $updates['current_page'] = substr($currentPage, 0, 500);
        }
        if ($panelOpen && $conversation->unread_visitor_count > 0) {
            $updates['unread_visitor_count'] = 0;
            ChatMessage::where('conversation_id', $conversation->id)
                ->where('sender_type', 'admin')
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }
        $conversation->update($updates);

        $messagesQuery = ChatMessage::where('conversation_id', $conversation->id)
            ->orderBy('id');

        if ($sinceId > 0) {
            $messagesQuery->where('id', '>', $sinceId);
        }

        $messages = $messagesQuery->get([
            'id',
            'conversation_id',
            'sender_type',
            'body',
            'attachment_path',
            'read_at',
            'created_at',
        ]);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'visitor_name' => $conversation->visitor_name,
                'visitor_email' => $conversation->visitor_email,
            ],
            'messages' => $messages,
            'unread_count' => (int) $conversation->unread_visitor_count,
            'is_online' => $this->isStaffOnline(),
            'greeting' => SettingService::get('chat_greeting', 'We usually reply within a few minutes.'),
            'offline_message' => SettingService::get('chat_offline_message', 'Our studio team is currently away. Leave your name and email and we will reply by email shortly.'),
        ]);
    }

    /**
     * Send a visitor message (creates conversation on first message).
     * POST /api/chat/messages
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_token' => 'nullable|string|max:100',
            'visitor_name' => 'nullable|string|max:100',
            'visitor_email' => 'nullable|email|max:150',
            'current_page' => 'nullable|string|max:500',
            'body' => 'nullable|string|max:1500',
            'attachment_path' => 'nullable|string|max:500',
        ]);

        $visitorToken = $this->resolveVisitorToken($request);
        if (! $visitorToken) {
            return response()->json([
                'message' => 'Visitor token is required.',
            ], 422);
        }

        $cleanBody = isset($validated['body']) ? trim(strip_tags($validated['body'])) : '';
        $attachment = $validated['attachment_path'] ?? null;

        if ($cleanBody === '' && empty($attachment)) {
            return response()->json([
                'message' => 'Please enter a message or attach an image.',
            ], 422);
        }

        $user = $request->user();

        $conversation = ChatConversation::firstOrCreate(
            ['visitor_token' => $visitorToken],
            [
                'user_id' => $user?->id,
                'visitor_name' => ! empty($validated['visitor_name'])
                    ? strip_tags(trim($validated['visitor_name']))
                    : ($user?->name ?: 'Guest Visitor'),
                'visitor_email' => ! empty($validated['visitor_email'])
                    ? strtolower(trim($validated['visitor_email']))
                    : $user?->email,
                'current_page' => $validated['current_page'] ?? '/',
                'status' => 'open',
                'last_message_at' => now(),
                'visitor_last_seen_at' => now(),
                'unread_admin_count' => 0,
                'unread_visitor_count' => 0,
            ]
        );

        // Update visitor metadata if newly supplied
        $convUpdates = [
            'status' => 'open',
            'last_message_at' => now(),
            'visitor_last_seen_at' => now(),
            'unread_admin_count' => $conversation->unread_admin_count + 1,
        ];
        if (! empty($validated['visitor_name'])) {
            $convUpdates['visitor_name'] = strip_tags(trim($validated['visitor_name']));
        }
        if (! empty($validated['visitor_email'])) {
            $convUpdates['visitor_email'] = strtolower(trim($validated['visitor_email']));
        }
        if (! empty($validated['current_page'])) {
            $convUpdates['current_page'] = $validated['current_page'];
        }
        if ($user && ! $conversation->user_id) {
            $convUpdates['user_id'] = $user->id;
        }
        $conversation->update($convUpdates);

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'visitor',
            'sender_id' => $user?->id,
            'body' => $cleanBody,
            'attachment_path' => $attachment,
        ]);

        return response()->json([
            'message' => 'Message sent.',
            'data' => $message,
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'visitor_name' => $conversation->visitor_name,
                'visitor_email' => $conversation->visitor_email,
            ],
        ], 201);
    }

    /**
     * Upload an optional image attachment in visitor chat.
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

    /**
     * Merge visitor token chat conversation with logged-in user account on login.
     */
    public function merge(Request $request): JsonResponse
    {
        $user = $request->user();
        $visitorToken = $this->resolveVisitorToken($request);

        if (! $user || ! $visitorToken) {
            return response()->json(['status' => 'skipped']);
        }

        $conversation = ChatConversation::where('visitor_token', $visitorToken)->first();
        if ($conversation) {
            $conversation->update([
                'user_id' => $user->id,
                'visitor_name' => $conversation->visitor_name ?: $user->name,
                'visitor_email' => $conversation->visitor_email ?: $user->email,
            ]);
        }

        return response()->json([
            'status' => 'merged',
            'conversation_id' => $conversation?->id,
        ]);
    }
}

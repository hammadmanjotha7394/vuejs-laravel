<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_token',
        'user_id',
        'visitor_name',
        'visitor_email',
        'current_page',
        'status',
        'assigned_admin_id',
        'last_message_at',
        'visitor_last_seen_at',
        'unread_admin_count',
        'unread_visitor_count',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'visitor_last_seen_at' => 'datetime',
            'unread_admin_count' => 'integer',
            'unread_visitor_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }
}

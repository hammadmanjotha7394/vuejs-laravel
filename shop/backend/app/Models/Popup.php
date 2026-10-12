<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Popup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'title',
        'body',
        'image',
        'button_text',
        'button_url',
        'trigger',
        'trigger_value',
        'show_on_pages_json',
        'frequency',
        'starts_at',
        'ends_at',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'trigger_value' => 'integer',
            'sort_order' => 'integer',
            'show_on_pages_json' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}

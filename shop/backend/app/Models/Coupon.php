<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_subtotal',
        'min_qty',
        'max_uses',
        'used_count',
        'per_user_limit',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_subtotal' => 'decimal:2',
            'min_qty' => 'integer',
            'used_count' => 'integer',
            'max_uses' => 'integer',
            'per_user_limit' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isValid(float $subtotal = 0, ?int $userId = null, int $itemCount = 1): bool
    {
        return $this->getValidationErrorMessage($subtotal, $userId, $itemCount) === null;
    }

    public function getValidationErrorMessage(float $subtotal = 0, ?int $userId = null, int $itemCount = 1): ?string
    {
        if (! $this->is_active) {
            return 'This coupon is inactive.';
        }

        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return 'This coupon is not yet active.';
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return 'This coupon has expired.';
        }

        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return 'This coupon has reached its maximum total usage limit.';
        }

        if ($this->min_subtotal && $subtotal < (float) $this->min_subtotal) {
            return 'A minimum cart subtotal of $'.number_format((float) $this->min_subtotal, 2).' is required for this coupon.';
        }

        if ($this->min_qty && $itemCount < (int) $this->min_qty) {
            return "Add at least {$this->min_qty} items to your bag to use coupon {$this->code}.";
        }

        if ($userId && $this->per_user_limit) {
            $userUses = Order::where('user_id', $userId)
                ->where('coupon_code', $this->code)
                ->whereNotIn('status', ['cancelled'])
                ->count();

            if ($userUses >= $this->per_user_limit) {
                return 'You have reached the maximum allowed usage limit for this coupon.';
            }
        }

        return null;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($this->type === 'free_shipping') {
            return 0.00;
        }

        if ($this->type === 'percent') {
            $discount = ($subtotal * (float) $this->value) / 100;
        } else {
            $discount = (float) $this->value;
        }

        return min($subtotal, round((float) $discount, 2));
    }
}

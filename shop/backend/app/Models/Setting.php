<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'is_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    public function getDecryptedValue(): ?string
    {
        if (! $this->is_encrypted || empty($this->value)) {
            return $this->value;
        }

        try {
            return Crypt::decryptString($this->value);
        } catch (DecryptException) {
            return $this->value;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (! $setting) {
            return $default;
        }

        return $setting->getDecryptedValue() ?? $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general', bool $isEncrypted = false): static
    {
        $storedValue = ($isEncrypted && ! empty($value)) ? Crypt::encryptString((string) $value) : $value;

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'group' => $group,
                'is_encrypted' => $isEncrypted,
            ]
        );
    }
}

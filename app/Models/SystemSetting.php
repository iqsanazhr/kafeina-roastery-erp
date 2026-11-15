<?php

declare(strict_types=1);

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'label',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (SystemSetting $setting) {
            Cache::forget("system_setting_{$setting->key}");
            Cache::forget('system_settings_all_grouped');
        });

        static::deleted(function (SystemSetting $setting) {
            Cache::forget("system_setting_{$setting->key}");
            Cache::forget('system_settings_all_grouped');
        });
    }

    /**
     * Get setting value by key with runtime cache and proper type-casting.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("system_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            if ($setting->value === null) {
                return $default;
            }

            return match ($setting->type) {
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $setting->value,
                'decimal' => (float) $setting->value,
                'json' => json_decode((string) $setting->value, true) ?? $default,
                'encrypted' => self::decryptSafely((string) $setting->value, $default),
                default => (string) $setting->value,
            };
        });
    }

    /**
     * Safely decrypt string with fallback.
     */
    protected static function decryptSafely(string $payload, mixed $fallback): mixed
    {
        try {
            return Crypt::decryptString($payload);
        } catch (Exception) {
            return $payload; // If raw or already decrypted
        }
    }

    /**
     * Set or update a setting value.
     */
    public static function set(
        string $key,
        mixed $value,
        ?string $group = null,
        ?string $type = null,
        ?string $label = null,
        ?string $description = null
    ): self {
        $setting = static::firstOrNew(['key' => $key]);

        if ($group !== null) {
            $setting->group = $group;
        } elseif (! $setting->group) {
            $setting->group = 'general';
        }

        if ($type !== null) {
            $setting->type = $type;
        } elseif (! $setting->type) {
            $setting->type = is_bool($value) ? 'boolean' : (is_array($value) ? 'json' : 'string');
        }

        if ($label !== null) {
            $setting->label = $label;
        } elseif (! $setting->label) {
            $setting->label = ucwords(str_replace('_', ' ', $key));
        }

        if ($description !== null) {
            $setting->description = $description;
        }

        // Serialize value
        if ($setting->type === 'encrypted' && ! empty($value)) {
            $setting->value = Crypt::encryptString((string) $value);
        } elseif ($setting->type === 'json' || is_array($value)) {
            $setting->value = json_encode($value);
        } elseif ($setting->type === 'boolean') {
            $setting->value = $value ? '1' : '0';
        } else {
            $setting->value = $value !== null ? (string) $value : null;
        }

        $setting->save();
        Cache::forget("system_setting_{$key}");

        return $setting;
    }
}

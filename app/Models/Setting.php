<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $cacheKey = "settings:{$key}";

        try {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        } catch (Throwable $e) {
            Log::warning('Setting cache read failed; falling back to database.', [
                'key' => $key,
                'exception' => $e->getMessage(),
            ]);
        }

        try {
            $setting = static::where('key', $key)->first();
        } catch (Throwable $e) {
            Log::error('Setting database read failed.', [
                'key' => $key,
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }

        $value = $setting?->value ?? $default;

        if ($value !== null) {
            try {
                Cache::put($cacheKey, $value, 3600);
            } catch (Throwable $e) {
                Log::warning('Setting cache write failed; returning database value.', [
                    'key' => $key,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return $value;
    }

    public static function setValue(string $key, ?string $value): self
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        try {
            Cache::forget("settings:{$key}");
        } catch (Throwable $e) {
            Log::warning('Setting cache invalidation failed after database update.', [
                'key' => $key,
                'exception' => $e->getMessage(),
            ]);
        }

        return $setting;
    }
}


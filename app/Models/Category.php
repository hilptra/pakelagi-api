<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property array<string>|null $measurement_fields
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Category extends Model
{
    use HasFactory;

    public const PUBLIC_CACHE_KEY = 'categories:public';

    public const PUBLIC_CACHE_TTL_SECONDS = 300;

    protected $fillable = [
        'name',
        'slug',
        'measurement_fields',
    ];

    protected function casts(): array
    {
        return ['measurement_fields' => 'array'];
    }

    /**
     * Ambil daftar kategori publik terurut nama dengan cache best-effort (TTL 300 detik).
     *
     * @return Collection<int, static>
     */
    public static function cachedPublicList(): Collection
    {
        try {
            $cached = Cache::get(self::PUBLIC_CACHE_KEY);
            if ($cached instanceof Collection) {
                return $cached;
            }
        } catch (Throwable $e) {
            Log::warning('Public categories cache read failed; falling back to database.', [
                'exception' => $e->getMessage(),
            ]);
        }

        $categories = static::orderBy('name')->get();

        try {
            Cache::put(self::PUBLIC_CACHE_KEY, $categories, self::PUBLIC_CACHE_TTL_SECONDS);
        } catch (Throwable $e) {
            Log::warning('Public categories cache write failed; returning database result.', [
                'exception' => $e->getMessage(),
            ]);
        }

        return $categories;
    }

    /**
     * Hapus cache daftar kategori publik setelah mutasi admin berhasil.
     */
    public static function forgetPublicCache(): void
    {
        try {
            Cache::forget(self::PUBLIC_CACHE_KEY);
        } catch (Throwable $e) {
            Log::warning('Failed to invalidate public categories cache.', [
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}

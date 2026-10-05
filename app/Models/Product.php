<?php

namespace App\Models;

use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $price
 * @property string|null $brand
 * @property string $size_label
 * @property ProductCondition $condition
 * @property string|null $condition_notes
 * @property array<string, mixed>|null $measurements
 * @property ProductStatus $status
 * @property Carbon|null $sold_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Category|null $category
 * @property-read ProductImage|null $primaryImage
 * @property-read Collection<int, ProductImage> $images
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'brand',
        'size_label',
        'condition',
        'condition_notes',
        'measurements',
        'status',
        'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'condition' => ProductCondition::class,
            'status' => ProductStatus::class,
            'measurements' => 'array',
            'sold_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', '!=', ProductStatus::Hidden);
    }

    /**
     * @return HasOne<ProductImage, $this>
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['category'] ?? null, fn (Builder $q, string $slug) => $q->whereHas(
                'category', fn (Builder $c) => $c->where('slug', $slug)
            ))
            ->when($filters['size'] ?? null, fn (Builder $q, string $size) => $q->where('size_label', $size))
            ->when(isset($filters['min_price']), fn (Builder $q) => $q->where('price', '>=', (int) $filters['min_price']))
            ->when(isset($filters['max_price']), fn (Builder $q) => $q->where('price', '<=', (int) $filters['max_price']))
            ->when($filters['q'] ?? null, function (Builder $q, string $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $q->where(fn (Builder $w) => $w
                    ->where('name', 'like', $like)
                    ->orWhere('brand', 'like', $like)
                    ->orWhere('description', 'like', $like));
            })
            ->when($filters['slugs'] ?? null, fn (Builder $q, array $slugs) => $q->whereIn('slug', $slugs));
    }

    public function scopeSoldLast(Builder $query): Builder
    {
        return $query->orderByRaw('status = ?', [ProductStatus::Sold->value]);
    }

    public function scopeSortedBy(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('price')->orderByDesc('id'),
            'price_desc' => $query->orderByDesc('price')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };
    }
}

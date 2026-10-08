<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property Carbon $event_date
 * @property Carbon|null $end_date
 * @property string $location
 * @property string|null $organizer
 * @property string|null $cover_image
 * @property string|null $cover_image_thumbnail
 * @property EventStatus $status
 * @property bool $show_on_homepage
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, EventImage> $images
 */
class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'event_date',
        'end_date',
        'location',
        'organizer',
        'cover_image',
        'cover_image_thumbnail',
        'status',
        'show_on_homepage',
    ];

    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'event_date' => 'datetime',
            'end_date' => 'datetime',
            'show_on_homepage' => 'boolean',
        ];
    }

    /**
     * @return HasMany<EventImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(EventImage::class)->orderBy('sort_order');
    }

    /**
     * Scope for published events only
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Published->value);
    }

    /**
     * Scope for upcoming events (event_date in future or end_date in future)
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        $now = now();

        return $query->where(function (Builder $q) use ($now) {
            $q->where('event_date', '>=', $now)
                ->orWhere(function (Builder $q2) use ($now) {
                    $q2->whereNotNull('end_date')->where('end_date', '>=', $now);
                });
        });
    }

    /**
     * Scope for past events (event_date in past and end_date in past or null)
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopePast(Builder $query): Builder
    {
        $now = now();

        return $query->where('event_date', '<', $now)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '<', $now);
            });
    }

    /**
     * Scope for homepage visibility
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeHomepage(Builder $query): Builder
    {
        return $query->where('show_on_homepage', true);
    }

    /**
     * Filter query
     *
     * @param  Builder<Event>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Event>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('organizer', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when(($filters['type'] ?? null) === 'upcoming', fn (Builder $q) => $q->upcoming())
            ->when(($filters['type'] ?? null) === 'past', fn (Builder $q) => $q->past())
            ->when(isset($filters['homepage']), fn (Builder $q) => $q->where('show_on_homepage', (bool) $filters['homepage']));
    }

    /**
     * Sort order scope
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeSortedBy(Builder $query, ?string $sort = null): Builder
    {
        return match ($sort) {
            'date_asc' => $query->orderBy('event_date', 'asc'),
            'date_desc' => $query->orderBy('event_date', 'desc'),
            'created_desc' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('event_date', 'desc'),
        };
    }
}

<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Event
 */
class EventListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $disk = Storage::disk(config('pakelagi.images.disk', 'public'));
        $now = now();
        $isUpcoming = $this->event_date->gte($now) || ($this->end_date && $this->end_date->gte($now));

        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'event_date' => $this->event_date->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),
            'location' => $this->location,
            'organizer' => $this->organizer,
            'cover_image_url' => $this->cover_image ? $disk->url($this->cover_image) : null,
            'cover_image_thumbnail_url' => $this->cover_image_thumbnail ? $disk->url($this->cover_image_thumbnail) : ($this->cover_image ? $disk->url($this->cover_image) : null),
            'status' => $this->status->value,
            'timing' => $isUpcoming ? 'upcoming' : 'past',
            'is_upcoming' => $isUpcoming,
            'show_on_homepage' => $this->show_on_homepage,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}

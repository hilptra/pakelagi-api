<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Services\EventImageProcessor;
use App\Services\FrontendCache;
use App\Support\UniqueSlug;
use Illuminate\Http\UploadedFile;

class CreateEvent
{
    public function __construct(
        private readonly EventImageProcessor $processor,
        private readonly FrontendCache $frontend,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?UploadedFile $coverImage = null): Event
    {
        $slug = UniqueSlug::make(Event::class, $data['title'], 'event');
        $status = $data['status'] ?? EventStatus::Draft->value;

        $event = Event::create([
            ...$data,
            'slug' => $slug,
            'status' => $status,
            'show_on_homepage' => (bool) ($data['show_on_homepage'] ?? false),
        ]);

        if ($coverImage) {
            $processed = $this->processor->store($coverImage, $event->id);
            $event->update([
                'cover_image' => $processed['path'],
                'cover_image_thumbnail' => $processed['thumbnail_path'],
            ]);
        }

        $this->frontend->event($event);

        return $event;
    }
}

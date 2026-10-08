<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Services\EventImageProcessor;
use App\Services\FrontendCache;
use Illuminate\Http\UploadedFile;

class UpdateEvent
{
    public function __construct(
        private readonly EventImageProcessor $processor,
        private readonly FrontendCache $frontend,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Event $event, array $data, ?UploadedFile $coverImage = null, bool $removeCover = false): Event
    {
        $updateData = $data;
        // Permalinks/slugs do not change after creation per project rules
        unset($updateData['slug']);

        if ($removeCover && $event->cover_image) {
            $this->processor->delete([$event->cover_image, $event->cover_image_thumbnail]);
            $updateData['cover_image'] = null;
            $updateData['cover_image_thumbnail'] = null;
        }

        if ($coverImage) {
            if ($event->cover_image) {
                $this->processor->delete([$event->cover_image, $event->cover_image_thumbnail]);
            }
            $processed = $this->processor->store($coverImage, $event->id);
            $updateData['cover_image'] = $processed['path'];
            $updateData['cover_image_thumbnail'] = $processed['thumbnail_path'];
        }

        $event->update($updateData);

        $this->frontend->event($event);

        return $event;
    }
}

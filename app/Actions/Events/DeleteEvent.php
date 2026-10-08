<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Services\EventImageProcessor;
use App\Services\FrontendCache;

class DeleteEvent
{
    public function __construct(
        private readonly EventImageProcessor $processor,
        private readonly FrontendCache $frontend,
    ) {}

    public function execute(Event $event): void
    {
        // Gather all image paths to delete from disk
        $pathsToDelete = [];
        if ($event->cover_image) {
            $pathsToDelete[] = $event->cover_image;
            $pathsToDelete[] = $event->cover_image_thumbnail;
        }

        foreach ($event->images as $image) {
            $pathsToDelete[] = $image->path;
            $pathsToDelete[] = $image->thumbnail_path;
        }

        $this->processor->delete($pathsToDelete);

        $slug = $event->slug;
        $event->delete();

        $this->frontend->event();
    }
}

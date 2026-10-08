<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\EventImage;
use App\Services\EventImageProcessor;
use App\Services\FrontendCache;

class DeleteEventImage
{
    public function __construct(
        private readonly EventImageProcessor $processor,
        private readonly FrontendCache $frontend,
    ) {}

    public function execute(Event $event, EventImage $image): void
    {
        $this->processor->delete([$image->path, $image->thumbnail_path]);
        $image->delete();

        $this->frontend->event($event);
    }
}

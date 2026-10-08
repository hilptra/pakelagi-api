<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\EventImage;
use App\Services\EventImageProcessor;
use App\Services\FrontendCache;
use Illuminate\Http\UploadedFile;

class UploadEventImages
{
    public function __construct(
        private readonly EventImageProcessor $processor,
        private readonly FrontendCache $frontend,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, EventImage>
     */
    public function execute(Event $event, array $files, ?string $caption = null): array
    {
        $maxSort = (int) $event->images()->max('sort_order');
        $createdImages = [];

        foreach ($files as $file) {
            $maxSort++;
            $processed = $this->processor->store($file, $event->id);

            $image = $event->images()->create([
                'path' => $processed['path'],
                'thumbnail_path' => $processed['thumbnail_path'],
                'caption' => $caption,
                'sort_order' => $maxSort,
            ]);

            $createdImages[] = $image;
        }

        $this->frontend->event($event);

        return $createdImages;
    }
}

<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Services\FrontendCache;

class ReorderEventImages
{
    public function __construct(
        private readonly FrontendCache $frontend,
    ) {}

    /**
     * @param  array<int, int>  $imageIds
     */
    public function execute(Event $event, array $imageIds): void
    {
        foreach ($imageIds as $order => $id) {
            $event->images()->where('id', $id)->update(['sort_order' => $order + 1]);
        }

        $this->frontend->event($event);
    }
}

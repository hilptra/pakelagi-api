<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;

/**
 * @mixin Event
 */
class EventResource extends EventListResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'images' => EventImageResource::collection($this->whenLoaded('images')),
        ];
    }
}

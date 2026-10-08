<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;

/**
 * @mixin Event
 */
class AdminEventResource extends AdminEventListResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'images' => AdminEventImageResource::collection($this->whenLoaded('images')),
        ];
    }
}

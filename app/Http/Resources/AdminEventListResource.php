<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;

/**
 * @mixin Event
 */
class AdminEventListResource extends EventListResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            ...parent::toArray($request),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\EventImage;
use Illuminate\Http\Request;

/**
 * @mixin EventImage
 */
class AdminEventImageResource extends EventImageResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            ...parent::toArray($request),
        ];
    }
}

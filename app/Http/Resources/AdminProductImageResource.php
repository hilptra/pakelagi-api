<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AdminProductImageResource extends ProductImageResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            ...parent::toArray($request),
        ];
    }
}

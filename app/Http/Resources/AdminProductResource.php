<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AdminProductResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            ...parent::toArray($request),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
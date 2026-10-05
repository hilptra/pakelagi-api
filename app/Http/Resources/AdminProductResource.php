<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;

/**
 * @mixin Product
 */
class AdminProductResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            ...parent::toArray($request),
            'images' => AdminProductImageResource::collection($this->whenLoaded('images')),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

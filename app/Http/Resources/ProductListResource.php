<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'price' => $this->price,
            'size_label' => $this->size_label,
            'condition' => $this->condition->value,
            'status' => $this->status->value,
            'sold_at' => $this->sold_at?->toIso8601String(),
            'category' => [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],
            'primary_image' => $this->whenLoaded(
                'primaryImage',
                fn () => $this->primaryImage ? new ProductImageResource($this->primaryImage) : null,
            ),
        ];
    }
}

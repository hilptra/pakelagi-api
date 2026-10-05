<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'brand' => $this->brand,
            'size_label' => $this->size_label,
            'condition' => $this->condition->value,
            'condition_notes' => $this->condition_notes,
            'measurements' => $this->measurements,
            'status' => $this->status->value,
            'sold_at' => $this->sold_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
        ];
    }
}

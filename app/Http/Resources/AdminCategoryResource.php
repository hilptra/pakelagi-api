<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;

/**
 * @mixin Category
 */
class AdminCategoryResource extends CategoryResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            ...parent::toArray($request),
            'products_count' => $this->whenCounted('products'),
        ];
    }
}

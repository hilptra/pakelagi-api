<?php

namespace App\Http\Resources;

use App\Models\ProductImage;
use Illuminate\Http\Request;

/**
 * @mixin ProductImage
 */
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

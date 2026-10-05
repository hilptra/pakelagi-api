<?php

namespace App\Http\Resources;

use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin ProductImage
 */
class ProductImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $disk = Storage::disk(config('pakelagi.images.disk'));

        return [
            'url' => $disk->url($this->path),
            'thumbnail_url' => $this->thumbnail_path ? $disk->url($this->thumbnail_path) : null,
            'is_primary' => $this->is_primary,
            'sort_order' => $this->sort_order,
        ];
    }
}

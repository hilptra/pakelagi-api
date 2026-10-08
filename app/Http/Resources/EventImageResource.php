<?php

namespace App\Http\Resources;

use App\Models\EventImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin EventImage
 */
class EventImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $disk = Storage::disk(config('pakelagi.images.disk', 'public'));

        return [
            'url' => $disk->url($this->path),
            'thumbnail_url' => $this->thumbnail_path ? $disk->url($this->thumbnail_path) : $disk->url($this->path),
            'caption' => $this->caption,
            'sort_order' => $this->sort_order,
        ];
    }
}

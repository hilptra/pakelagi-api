<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;

class ProductImageProcessor
{
    /**
     * Simpan satu foto dalam dua ukuran (penuh dan thumbnail), format WebP.
     *
     * @return array{path: string, thumbnail_path: string}
     */
    public function store(UploadedFile $file, int $productId): array
    {
        $name = Str::uuid()->toString();
        $path = "products/{$productId}/{$name}.webp";
        $thumbnailPath = "products/{$productId}/{$name}_thumb.webp";

        // Encode keduanya dulu sebelum menulis, supaya kegagalan tidak meninggalkan file setengah jadi.
        $full = $this->encode($file, (int) config('pakelagi.images.full_size'));
        $thumbnail = $this->encode($file, (int) config('pakelagi.images.thumbnail_size'));

        $this->disk()->put($path, $full);
        $this->disk()->put($thumbnailPath, $thumbnail);

        return ['path' => $path, 'thumbnail_path' => $thumbnailPath];
    }

    /**
     * @param  iterable<int, string|null>  $paths
     */
    public function delete(iterable $paths): void
    {
        $paths = collect($paths)->filter()->values()->all();

        if ($paths !== []) {
            $this->disk()->delete($paths);
        }
    }

    private function encode(UploadedFile $file, int $maxSide): string
    {
        return Image::decode($file)
            ->scaleDown(width: $maxSide, height: $maxSide)
            ->encode(new WebpEncoder(quality: (int) config('pakelagi.images.quality')))
            ->toString();
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('pakelagi.images.disk'));
    }
}

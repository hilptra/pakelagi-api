<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;

class EventImageProcessor
{
    /**
     * Simpan satu foto event dalam dua ukuran (penuh dan thumbnail), format WebP.
     *
     * @return array{path: string, thumbnail_path: string}
     */
    public function store(UploadedFile $file, int $eventId): array
    {
        $name = Str::uuid()->toString();
        $path = "events/{$eventId}/{$name}.webp";
        $thumbnailPath = "events/{$eventId}/{$name}_thumb.webp";

        $full = $this->encode($file, (int) config('pakelagi.images.full_size', 1600));
        $thumbnail = $this->encode($file, (int) config('pakelagi.images.thumbnail_size', 480));

        $this->disk()->put($path, $full);
        $this->disk()->put($thumbnailPath, $thumbnail);

        return ['path' => $path, 'thumbnail_path' => $thumbnailPath];
    }

    /**
     * Hapus daftar file dari disk storage.
     *
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
            ->encode(new WebpEncoder(quality: (int) config('pakelagi.images.quality', 80)))
            ->toString();
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('pakelagi.images.disk', 'public'));
    }
}

<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $disk = Storage::disk(config('pakelagi.images.disk', 'public'));

        if (! $disk->exists('products/demo.webp')) {
            $disk->put('products/demo.webp', self::createPlaceholderWebp(800));
            $disk->put('products/demo_thumb.webp', self::createPlaceholderWebp(480));
        }

        Category::all()->each(function (Category $category) {
            $products = collect([])
                ->concat(Product::factory()->count(6)->forCategory($category)->create())
                ->concat(Product::factory()->count(2)->forCategory($category)->sold()->create())
                ->concat(Product::factory()->count(1)->forCategory($category)->hidden()->create());

            foreach ($products as $product) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => 'products/demo.webp',
                    'thumbnail_path' => 'products/demo_thumb.webp',
                    'sort_order' => 1,
                    'is_primary' => true,
                ]);
            }
        });
    }

    /**
     * Generate a lightweight branded WebP placeholder image for local demo products.
     */
    public static function createPlaceholderWebp(int $size = 800): string
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagewebp')) {
            return base64_decode('UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAQAcJaQAA3AA/v3AgAA=');
        }

        $img = imagecreatetruecolor($size, $size);
        imageantialias($img, true);

        // PakeLagi palette
        $bg = imagecolorallocate($img, 248, 247, 244);       // #F8F7F4
        $accent = imagecolorallocate($img, 231, 220, 200);  // #E7DCC8
        $primary = imagecolorallocate($img, 30, 52, 71);    // #1E3447
        $secondary = imagecolorallocate($img, 107, 127, 107); // #6B7F6B
        $border = imagecolorallocate($img, 229, 231, 235);  // #E5E7EB

        imagefilledrectangle($img, 0, 0, $size, $size, $bg);

        $cx = (int) ($size / 2);
        $cy = (int) ($size * 0.44);

        // Soft circular backdrop
        $circleDiameter = (int) ($size * 0.46);
        imagefilledellipse($img, $cx, $cy, $circleDiameter, $circleDiameter, $accent);

        // Outer subtle border
        imagerectangle($img, 0, 0, $size - 1, $size - 1, $border);

        // Hanger hook & shoulders
        $stroke = max(2, (int) round($size / 140));
        imagesetthickness($img, $stroke);

        $hookRadius = (int) ($size * 0.03);
        $hookY = $cy - (int) ($size * 0.11);
        imagearc($img, $cx, $hookY, $hookRadius * 2, $hookRadius * 2, 180, 40, $primary);
        imageline($img, $cx, $hookY + $hookRadius, $cx, $hookY + $hookRadius + (int) ($size * 0.025), $primary);

        $shoulderY = $hookY + $hookRadius + (int) ($size * 0.025);
        $hangerHalfW = (int) ($size * 0.13);
        $hangerBottomY = $shoulderY + (int) ($size * 0.065);
        imageline($img, $cx, $shoulderY, $cx - $hangerHalfW, $hangerBottomY, $primary);
        imageline($img, $cx, $shoulderY, $cx + $hangerHalfW, $hangerBottomY, $primary);
        imageline($img, $cx - $hangerHalfW, $hangerBottomY, $cx + $hangerHalfW, $hangerBottomY, $primary);

        // Stylized garment body hanging below
        $shirtTop = $hangerBottomY + (int) ($size * 0.015);
        $shirtW = (int) ($size * 0.16);
        $shirtH = (int) ($size * 0.15);
        $sleeveW = (int) ($size * 0.045);
        $sleeveH = (int) ($size * 0.06);

        $garment = [
            $cx - (int) ($shirtW / 2), $shirtTop,
            $cx + (int) ($shirtW / 2), $shirtTop,
            $cx + (int) ($shirtW / 2) + $sleeveW, $shirtTop + $sleeveH,
            $cx + (int) ($shirtW / 2), $shirtTop + $sleeveH + (int) ($size * 0.01),
            $cx + (int) ($shirtW / 2), $shirtTop + $shirtH,
            $cx - (int) ($shirtW / 2), $shirtTop + $shirtH,
            $cx - (int) ($shirtW / 2), $shirtTop + $sleeveH + (int) ($size * 0.01),
            $cx - (int) ($shirtW / 2) - $sleeveW, $shirtTop + $sleeveH,
        ];
        imagefilledpolygon($img, $garment, $secondary);

        // Brand text centered below circle
        $title = 'PAKELAGI';
        $subtitle = 'PRELOVED CURATED';
        $font = 5;
        $subFont = 3;

        $titleW = imagefontwidth($font) * strlen($title);
        $subW = imagefontwidth($subFont) * strlen($subtitle);
        $textY = $cy + (int) ($circleDiameter / 2) + (int) ($size * 0.05);

        imagestring($img, $font, $cx - (int) ($titleW / 2), $textY, $title, $primary);
        imagestring($img, $subFont, $cx - (int) ($subW / 2), $textY + 24, $subtitle, $secondary);

        ob_start();
        imagewebp($img, null, 85);
        $bytes = ob_get_clean();
        imagedestroy($img);

        return is_string($bytes) ? $bytes : '';
    }
}

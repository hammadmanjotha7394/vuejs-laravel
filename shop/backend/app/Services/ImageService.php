<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageService
{
    /**
     * Process and store an uploaded image into 3 WebP sizes (thumb 300px, medium 800px, large 1600px).
     *
     * @return array{path: string, thumb_path: string, medium_path: string, large_path: string}
     */
    public static function storeProductImage(UploadedFile $file): array
    {
        $manager = new ImageManager(new Driver);
        $image = $manager->read($file->getRealPath());

        $hash = uniqid('prod_');

        // 1. Large (max width 1600px)
        $largeImage = clone $image;
        if ($largeImage->width() > 1600) {
            $largeImage = $largeImage->scaleDown(width: 1600);
        }
        $largeEncoded = (string) $largeImage->toWebp(85);
        $largePath = "products/{$hash}_large.webp";
        Storage::disk('public')->put($largePath, $largeEncoded);

        // 2. Medium (max width 800px)
        $mediumImage = clone $image;
        if ($mediumImage->width() > 800) {
            $mediumImage = $mediumImage->scaleDown(width: 800);
        }
        $mediumEncoded = (string) $mediumImage->toWebp(82);
        $mediumPath = "products/{$hash}_medium.webp";
        Storage::disk('public')->put($mediumPath, $mediumEncoded);

        // 3. Thumb (max width 300px)
        $thumbImage = clone $image;
        if ($thumbImage->width() > 300) {
            $thumbImage = $thumbImage->scaleDown(width: 300);
        }
        $thumbEncoded = (string) $thumbImage->toWebp(80);
        $thumbPath = "products/{$hash}_thumb.webp";
        Storage::disk('public')->put($thumbPath, $thumbEncoded);

        return [
            'path' => $largePath,
            'large_path' => $largePath,
            'medium_path' => $mediumPath,
            'thumb_path' => $thumbPath,
        ];
    }
}

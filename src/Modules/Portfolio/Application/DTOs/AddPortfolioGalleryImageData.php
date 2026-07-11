<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\DTOs;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

/**
 * Payload to append one image to a portfolio's gallery collection. One file per
 * request keeps the upload flow simple and auditable (BACKEND-PHP §7 Insecure
 * Design) — the frontend calls this endpoint once per selected file.
 */
final class AddPortfolioGalleryImageData extends Data
{
    public function __construct(
        public UploadedFile $image,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:4096', // 4 MB
                'dimensions:max_width=4096,max_height=4096',
            ],
        ];
    }
}

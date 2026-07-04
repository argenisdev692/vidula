<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

/**
 * Self-service profile photo upload payload.
 *
 * The file is validated as a real image (MIME + extension + size + dimensions)
 * before it ever reaches the storage adapter. Persisted to Cloudflare R2 with
 * private visibility — exposed only through signed temporary URLs (BACKEND-PHP §5).
 */
final class UpdateProfilePhotoData extends Data
{
    public function __construct(
        public UploadedFile $photo,
    ) {}

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120', // 5 MB
                'dimensions:max_width=4096,max_height=4096',
            ],
        ];
    }
}

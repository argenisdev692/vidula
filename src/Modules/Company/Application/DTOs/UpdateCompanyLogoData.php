<?php

declare(strict_types=1);

namespace Modules\Company\Application\DTOs;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

/**
 * Branding logo upload payload. Exactly one of the three brand variants
 * (`logo`, `logo_white`, `mark`) is replaced per request.
 *
 * PNG only, validated as a real image (MIME + extension + magic bytes + size +
 * dimensions) before it reaches R2. Stored with public visibility so emails and
 * the login hero can load it via a permanent URL (BACKEND-PHP §5 exception for
 * public brand assets — never used for private user uploads).
 */
final class UpdateCompanyLogoData extends Data
{
    public function __construct(
        public string $type,
        public UploadedFile $logo,
    ) {}

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:logo,logo_white,mark'],
            'logo' => [
                'required',
                'file',
                'image',
                'mimes:png',
                'mimetypes:image/png',
                'max:2048', // 2 MB
                'dimensions:max_width=2048,max_height=2048',
            ],
        ];
    }
}

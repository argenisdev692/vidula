<?php

declare(strict_types=1);

namespace Modules\Company\Application\DTOs;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

/**
 * Company signature upload payload (PNG or PDF). Validated as a real file
 * (MIME + extension + magic bytes + size) before it reaches R2.
 *
 * Stored with public visibility so it can be rendered on emailed/generated
 * documents via a permanent URL — same BACKEND-PHP §5 exception granted to
 * public brand assets, never used for private user uploads.
 */
final class UpdateCompanySignatureData extends Data
{
    public function __construct(
        public UploadedFile $signature,
    ) {}

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'signature' => [
                'required',
                'file',
                'mimes:png,pdf',
                'mimetypes:image/png,application/pdf',
                'max:2048', // 2 MB
            ],
        ];
    }
}

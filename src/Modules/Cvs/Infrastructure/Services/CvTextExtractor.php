<?php

declare(strict_types=1);

namespace Modules\Cvs\Infrastructure\Services;

use Illuminate\Http\UploadedFile;
use Modules\Cvs\Domain\Enums\CvFileType;

/**
 * Extracts plain text from uploaded CV files. MD is read immediately;
 * PDF text extraction is deferred to Module 2 (no PDF parser dependency yet).
 */
final readonly class CvTextExtractor
{
    public function extract(?string $rawText, CvFileType $type, UploadedFile $file): ?string
    {
        return match ($type) {
            CvFileType::Md => $this->readMarkdown($file),
            CvFileType::Pdf => $rawText, // leave null until Module 2 PDF parsing
        };
    }

    private function readMarkdown(UploadedFile $file): string
    {
        $contents = file_get_contents($file->getRealPath() ?: $file->getPathname());

        if ($contents === false) {
            return '';
        }

        return $contents
            |> trim(...)
            |> (fn (string $text): string => mb_substr($text, 0, 500_000));
    }
}

<?php

declare(strict_types=1);

namespace Modules\Cvs\Domain\Ports;

use Modules\Cvs\Domain\Enums\CvFileType;

/**
 * Extracts plain text from an uploaded CV file for indexing / studio pipelines.
 * Domain-pure: only native types + module enums (no Illuminate imports).
 */
interface CvTextExtractorPort
{
    public function extract(?string $rawText, CvFileType $type, \SplFileInfo $file): ?string;
}

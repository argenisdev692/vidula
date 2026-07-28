<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Ports;

use Modules\Products\Application\DTOs\CourseDocumentData;

/**
 * Turns the content tree into the two operator-facing documents. Both methods
 * return raw contents, never a path or an HTTP response — where the bytes
 * land is the caller's decision (private disk via StoragePort).
 */
interface CourseRendererPort
{
    public function renderMarkdown(CourseDocumentData $document): string;

    /**
     * @return string Raw PDF bytes.
     */
    public function renderPdf(CourseDocumentData $document): string;
}

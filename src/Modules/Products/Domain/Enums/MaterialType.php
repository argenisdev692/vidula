<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Enums;

/**
 * Deliverable kinds attachable to a product. Video binaries are deliberately
 * absent in v1 (clarify Q2) — recordings live outside this module.
 */
enum MaterialType: string
{
    case Pdf = 'pdf';
    case Markdown = 'markdown';
    case Link = 'link';

    public function mimeType(): string
    {
        return match ($this) {
            self::Pdf => 'application/pdf',
            self::Markdown => 'text/markdown',
            self::Link => 'text/uri-list',
        };
    }
}

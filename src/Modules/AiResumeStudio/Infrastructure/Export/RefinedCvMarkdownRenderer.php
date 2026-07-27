<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Export;

use Illuminate\Support\Str;

/**
 * Converts refined CV Markdown into DomPDF-safe HTML.
 * Keeps a minimal ATS-friendly tag set (no tables, images, or scripts).
 */
final readonly class RefinedCvMarkdownRenderer
{
    private const string ALLOWED_TAGS = '<h1><h2><h3><p><ul><ol><li><strong><em><a><br>';

    public function toHtml(string $markdown): string
    {
        $normalized = $markdown
            |> trim(...)
            |> (fn (string $md): string => str_replace(["\r\n", "\r"], "\n", $md));

        if ($normalized === '') {
            return '<p></p>';
        }

        $html = Str::markdown($normalized, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $safe = strip_tags($html, self::ALLOWED_TAGS);

        return $safe !== '' ? $safe : '<p>'.e($normalized).'</p>';
    }
}

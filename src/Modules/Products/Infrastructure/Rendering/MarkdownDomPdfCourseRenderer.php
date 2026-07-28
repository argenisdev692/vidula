<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Rendering;

use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Products\Application\DTOs\CourseDocumentData;
use Modules\Products\Application\DTOs\CourseSessionData;
use Modules\Products\Application\DTOs\CourseTopicData;
use Modules\Products\Domain\Ports\CourseRendererPort;
use Shared\Infrastructure\Company\CompanyProfile;

/**
 * Renders course.md (string) and course.pdf (raw bytes) from the content tree.
 */
final readonly class MarkdownDomPdfCourseRenderer implements CourseRendererPort
{
    public function renderMarkdown(CourseDocumentData $document): string
    {
        $lines = [
            '# '.$document->title,
            '',
        ];

        if ($document->description !== null && $document->description !== '') {
            $lines[] = $document->description;
            $lines[] = '';
        }

        $lines[] = '_Type: '.$document->type->value.' · Language: '.$document->language.'_';
        $lines[] = '';

        foreach ($document->sessions as $session) {
            $lines = [...$lines, ...$this->sessionMarkdown($session)];
        }

        return implode("\n", $lines)."\n";
    }

    public function renderPdf(CourseDocumentData $document): string
    {
        $markdown = $this->renderMarkdown($document);

        return Pdf::loadView('products.pdf.course', [
            'document' => $document,
            'markdown' => $markdown,
            'company' => CompanyProfile::pdfBranding(),
        ])
            ->setPaper('a4', 'portrait')
            ->output();
    }

    /**
     * @return list<string>
     */
    private function sessionMarkdown(CourseSessionData $session): array
    {
        $lines = [
            '## '.$session->sessionNumber.'. '.$session->title,
            '',
        ];

        foreach ($session->topics as $topic) {
            $lines = [...$lines, ...$this->topicMarkdown($topic)];
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function topicMarkdown(CourseTopicData $topic): array
    {
        $lines = [
            '### '.$topic->sortOrder.'. '.$topic->title,
            '',
        ];

        if ($topic->intro !== null && $topic->intro !== '') {
            $lines[] = '#### Intro';
            $lines[] = $topic->intro;
            $lines[] = '';
        }

        if ($topic->body !== null && $topic->body !== '') {
            $lines[] = '#### Body';
            $lines[] = $topic->body;
            $lines[] = '';
        }

        if ($topic->outro !== null && $topic->outro !== '') {
            $lines[] = '#### Outro';
            $lines[] = $topic->outro;
            $lines[] = '';
        }

        if ($topic->notes !== null && $topic->notes !== '') {
            $lines[] = '#### Notes';
            $lines[] = $topic->notes;
            $lines[] = '';
        }

        return $lines;
    }
}

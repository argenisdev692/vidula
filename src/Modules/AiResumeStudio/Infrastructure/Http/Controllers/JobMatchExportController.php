<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\AiResumeStudio\Application\DTOs\StudioFilterData;
use Modules\AiResumeStudio\Domain\Ports\JobMatchRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Http\Export\JobMatchExportTransformer;
use Shared\Domain\Ports\ExportPort;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class JobMatchExportController
{
    public function __construct(private ExportPort $export, private JobMatchRepositoryPort $matches) {}

    public function __invoke(Request $request): StreamedResponse|Response
    {
        $format = (string) $request->string('format', 'csv');
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 422);

        $filters = StudioFilterData::validateAndCreate($request);
        $rows = $this->matches->lazyForExport($filters);

        return match ($format) {
            'pdf' => $this->export->pdf(
                'job-matches.pdf',
                'exports.pdf.job-matches',
                [
                    'rows' => $rows->map(JobMatchExportTransformer::transformForPdf(...)),
                    'generatedAt' => now()->format('F j, Y H:i'),
                ],
            ),
            default => $this->export->tabular(
                "job-matches.{$format}",
                ['Job Title', 'Company', 'Score', 'Application', 'Status', 'Source', 'URL', 'Owner', 'First Seen'],
                $rows->map(JobMatchExportTransformer::transformForTable(...)),
            ),
        };
    }
}

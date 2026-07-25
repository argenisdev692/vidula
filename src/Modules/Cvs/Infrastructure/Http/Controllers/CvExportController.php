<?php

declare(strict_types=1);

namespace Modules\Cvs\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Cvs\Application\DTOs\CvFilterData;
use Modules\Cvs\Infrastructure\Http\Export\CvExportTransformer;
use Modules\Cvs\Infrastructure\Persistence\Eloquent\Models\CvEloquentModel;
use Shared\Domain\Ports\ExportPort;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams filtered CVs as CSV / XLSX / PDF. Reuses CvFilterData + scopeApplyFilters.
 */
final readonly class CvExportController
{
    public function __construct(private ExportPort $export) {}

    public function __invoke(Request $request): StreamedResponse|Response
    {
        $format = (string) $request->string('format', 'csv');
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 422);

        $filters = CvFilterData::validateAndCreate($request);

        $rows = CvEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->with('user:id,first_name,last_name')
            ->orderByDesc('created_at')
            ->lazy();

        return match ($format) {
            'pdf' => $this->export->pdf(
                'cvs.pdf',
                'exports.pdf.cvs',
                [
                    'rows' => $rows->map(CvExportTransformer::transformForPdf(...)),
                    'generatedAt' => now()->format('F j, Y H:i'),
                ],
            ),
            default => $this->export->tabular(
                "cvs.{$format}",
                ['Title', 'Niche', 'Primary', 'Type', 'Filename', 'Owner', 'Created', 'Status'],
                $rows->map(CvExportTransformer::transformForTable(...)),
            ),
        };
    }
}

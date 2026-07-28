<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Students\Application\DTOs\StudentFilterData;
use Modules\Students\Infrastructure\Http\Export\StudentExportTransformer;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;
use Shared\Domain\Ports\ExportPort;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams filtered students as CSV / XLSX / PDF. Reuses StudentFilterData +
 * scopeApplyFilters (DRY) and Shared ExportPort.
 */
final readonly class StudentExportController
{
    public function __construct(private ExportPort $export) {}

    public function __invoke(Request $request): StreamedResponse|Response
    {
        $format = (string) $request->string('format', 'csv');
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 422);

        $filters = StudentFilterData::validateAndCreate($request);

        $rows = StudentEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->orderByDesc('created_at')
            ->lazy();

        return match ($format) {
            'pdf' => $this->export->pdf(
                'students.pdf',
                'exports.pdf.students',
                [
                    'rows' => $rows->map(StudentExportTransformer::transformForPdf(...)),
                    'generatedAt' => now()->format('F j, Y H:i'),
                ],
            ),
            default => $this->export->tabular(
                "students.{$format}",
                ['Name', 'Email', 'Phone', 'DNI', 'Lifecycle', 'Active', 'Created', 'Status'],
                $rows->map(StudentExportTransformer::transformForTable(...)),
            ),
        };
    }
}

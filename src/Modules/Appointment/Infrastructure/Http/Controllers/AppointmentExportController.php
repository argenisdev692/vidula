<?php

declare(strict_types=1);

namespace Modules\Appointment\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Appointment\Application\DTOs\AppointmentFilterData;
use Modules\Appointment\Infrastructure\Http\Export\AppointmentExportTransformer;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Shared\Domain\Ports\ExportPort;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the filtered lead pipeline as CSV / Excel / PDF. Reuses the SAME
 * {@see AppointmentFilterData} + `scopeApplyFilters()` as the list query (DRY)
 * and the Shared {@see ExportPort} mechanism — this module ships only the
 * transformer.
 */
final readonly class AppointmentExportController
{
    public function __construct(private ExportPort $export) {}

    public function __invoke(Request $request): StreamedResponse|Response
    {
        $format = (string) $request->string('format', 'csv');
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 422);

        $filters = AppointmentFilterData::validateAndCreate($request);

        $rows = AppointmentEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->orderByDesc('created_at')
            ->lazy();

        return match ($format) {
            'pdf' => $this->export->pdf(
                'appointments.pdf',
                'exports.pdf.appointments',
                [
                    'rows' => $rows->map(AppointmentExportTransformer::transformForPdf(...)),
                    'generatedAt' => now()->format('F j, Y H:i'),
                ],
            ),
            default => $this->export->tabular(
                "appointments.{$format}",
                ['Name', 'Company', 'Email', 'Phone', 'Lead Status', 'Meeting Status', 'Scheduled At', 'Created', 'Status'],
                $rows->map(AppointmentExportTransformer::transformForTable(...)),
            ),
        };
    }
}

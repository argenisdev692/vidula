<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Meeting\Application\DTOs\MeetingFilterData;
use Modules\Meeting\Infrastructure\Http\Export\MeetingExportTransformer;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;
use Shared\Domain\Ports\ExportPort;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the filtered meeting list as CSV / Excel / PDF — mirrors
 * `AppointmentExportController` exactly (same `ExportPort` mechanism, DRY).
 */
final readonly class MeetingExportController
{
    public function __construct(private ExportPort $export) {}

    public function __invoke(Request $request): StreamedResponse|Response
    {
        $format = (string) $request->string('format', 'csv');
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 422);

        $filters = MeetingFilterData::validateAndCreate($request);

        $rows = MeetingEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->withCount('attendees')
            ->with('organizer:id,uuid,first_name,last_name')
            ->orderByDesc('starts_at')
            ->lazy();

        return match ($format) {
            'pdf' => $this->export->pdf(
                'meetings.pdf',
                'exports.pdf.meetings',
                [
                    'rows' => $rows->map(MeetingExportTransformer::transformForPdf(...)),
                    'generatedAt' => now()->format('F j, Y H:i'),
                ],
            ),
            default => $this->export->tabular(
                "meetings.{$format}",
                ['Title', 'Organizer', 'Attendees', 'Status', 'Meeting Status', 'Starts At', 'Ends At', 'Created'],
                $rows->map(MeetingExportTransformer::transformForTable(...)),
            ),
        };
    }
}

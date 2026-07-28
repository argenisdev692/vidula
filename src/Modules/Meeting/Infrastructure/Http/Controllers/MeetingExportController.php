<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Meeting\Application\DTOs\MeetingFilterData;
use Modules\Meeting\Domain\Ports\MeetingRepositoryPort;
use Modules\Meeting\Infrastructure\Http\Export\MeetingExportTransformer;
use Shared\Domain\Ports\ExportPort;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the filtered meeting list as CSV / Excel / PDF — mirrors
 * `AppointmentExportController` (same `ExportPort` mechanism). Query building
 * lives on {@see MeetingRepositoryPort::lazyForExport()} so list + export share
 * one filter/eager-load/sort path (BACKEND-PHP §5.2 + §8).
 */
final readonly class MeetingExportController
{
    public function __construct(
        private ExportPort $export,
        private MeetingRepositoryPort $meetings,
    ) {}

    public function __invoke(Request $request): StreamedResponse|Response
    {
        $format = (string) $request->string('format', 'csv');
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 422);

        $filters = MeetingFilterData::validateAndCreate($request);
        $rows = $this->meetings->lazyForExport($filters);

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

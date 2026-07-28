<?php

declare(strict_types=1);

namespace Modules\Enrollments\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Enrollments\Application\Queries\GetAttendanceSheetHandler;
use Modules\Enrollments\Infrastructure\Http\Export\AttendanceSheetExportTransformer;
use Shared\Domain\Ports\ExportPort;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class AttendanceExportController
{
    public function __construct(private ExportPort $export) {}

    public function __invoke(
        string $classroomUuid,
        Request $request,
        GetAttendanceSheetHandler $get,
    ): StreamedResponse|Response {
        $format = (string) $request->string('format', 'csv');
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 422);

        $sheet = $get->handle($classroomUuid);
        $matrix = match ($format) {
            'pdf' => AttendanceSheetExportTransformer::transformForPdf($sheet),
            default => AttendanceSheetExportTransformer::transformForTable($sheet),
        };

        return match ($format) {
            'pdf' => $this->export->pdf(
                'attendance-sheet.pdf',
                'exports.pdf.attendance-sheet',
                [
                    'title' => $sheet['classroom']->product?->title ?? 'Attendance',
                    'headers' => $matrix['headers'],
                    'rows' => $matrix['rows'],
                    'generatedAt' => now()->format('F j, Y H:i'),
                ],
            ),
            default => $this->export->tabular(
                "attendance-sheet.{$format}",
                $matrix['headers'],
                collect($matrix['rows']),
            ),
        };
    }
}

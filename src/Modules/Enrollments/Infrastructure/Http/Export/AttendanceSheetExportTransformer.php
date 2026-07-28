<?php

declare(strict_types=1);

namespace Modules\Enrollments\Infrastructure\Http\Export;

/**
 * Builds a Lista Asistencia matrix: students as rows, sessions as columns.
 * Soft-delete Status is not applicable — this is a classroom attendance grid.
 */
final readonly class AttendanceSheetExportTransformer
{
    /**
     * @param  array{
     *     classroom: mixed,
     *     sessions: list<array<string, mixed>>,
     *     enrollments: list<array<string, mixed>>,
     *     marks: list<array<string, mixed>>
     * }  $sheet
     * @return array{headers: list<string>, rows: list<array<string, string>>}
     */
    #[\NoDiscard]
    public static function transformForTable(array $sheet): array
    {
        return $sheet
            |> self::extractMatrix(...)
            |> self::sanitizeCells(...);
    }

    /**
     * @param  array{
     *     classroom: mixed,
     *     sessions: list<array<string, mixed>>,
     *     enrollments: list<array<string, mixed>>,
     *     marks: list<array<string, mixed>>
     * }  $sheet
     * @return array{headers: list<string>, rows: list<array<string, string>>}
     */
    #[\NoDiscard]
    public static function transformForPdf(array $sheet): array
    {
        return self::transformForTable($sheet);
    }

    /**
     * @param  array{
     *     classroom: mixed,
     *     sessions: list<array<string, mixed>>,
     *     enrollments: list<array<string, mixed>>,
     *     marks: list<array<string, mixed>>
     * }  $sheet
     * @return array{headers: list<string>, rows: list<array<string, string>>}
     */
    private static function extractMatrix(array $sheet): array
    {
        $headers = ['Student', 'Email'];
        foreach ($sheet['sessions'] as $session) {
            $headers[] = self::sessionHeader($session);
        }

        $markMap = [];
        foreach ($sheet['marks'] as $mark) {
            $key = ($mark['enrollment_uuid'] ?? '').'|'.($mark['product_session_uuid'] ?? '');
            $status = (string) ($mark['attendance_status'] ?? '');
            $markMap[$key] = $status === '' ? '' : strtoupper(substr($status, 0, 1));
        }

        $rows = [];
        foreach ($sheet['enrollments'] as $enrollment) {
            $row = [
                'Student' => (string) ($enrollment['student']['name'] ?? ''),
                'Email' => (string) ($enrollment['student']['email'] ?? ''),
            ];

            foreach ($sheet['sessions'] as $session) {
                $header = self::sessionHeader($session);
                $key = ($enrollment['uuid'] ?? '').'|'.($session['uuid'] ?? '');
                $row[$header] = $markMap[$key] ?? '';
            }

            $rows[] = $row;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array{headers: list<string>, rows: list<array<string, string>>}  $matrix
     * @return array{headers: list<string>, rows: list<array<string, string>>}
     */
    private static function sanitizeCells(array $matrix): array
    {
        $matrix['rows'] = array_map(
            static fn (array $row): array => array_map(
                static fn (string $value): string => $value === '' ? '—' : $value,
                $row,
            ),
            $matrix['rows'],
        );

        return $matrix;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private static function sessionHeader(array $session): string
    {
        $label = 'S'.($session['session_number'] ?? '');
        if (! empty($session['session_date'])) {
            $label .= ' ('.$session['session_date'].')';
        }

        return $label;
    }
}

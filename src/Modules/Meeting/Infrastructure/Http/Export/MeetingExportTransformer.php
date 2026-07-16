<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Http\Export;

use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;

/**
 * Maps a {@see MeetingEloquentModel} row to export columns — mirrors
 * `AppointmentExportTransformer`. The module ships only this transformer; the
 * writer/streamer/PDF renderer live behind the Shared `ExportPort`.
 */
final readonly class MeetingExportTransformer
{
    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForTable(MeetingEloquentModel $meeting): array
    {
        return $meeting
            |> self::extractBaseData(...)
            |> self::formatDates(...);
    }

    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForPdf(MeetingEloquentModel $meeting): array
    {
        return self::transformForTable($meeting);
    }

    /**
     * `Status` is derived ONLY from `deleted_at` (Active/Suspended, BACKEND-PHP
     * §8). The meeting's own lifecycle (Scheduled/Cancelled) is a business
     * state and stays in its own `Meeting Status` column.
     *
     * @return array<string, string>
     */
    private static function extractBaseData(MeetingEloquentModel $meeting): array
    {
        return [
            'Title' => $meeting->title,
            'Organizer' => trim("{$meeting->organizer?->first_name} {$meeting->organizer?->last_name}") ?: '—',
            'Attendees' => (string) $meeting->attendees_count,
            'Status' => $meeting->deleted_at === null ? 'Active' : 'Suspended',
            'Meeting Status' => $meeting->status->value,
            'Starts At' => $meeting->starts_at->toDateTimeString(),
            'Ends At' => $meeting->ends_at->toDateTimeString(),
            'Created' => $meeting->created_at?->toDateTimeString() ?? '',
        ];
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private static function formatDates(array $row): array
    {
        foreach (['Starts At', 'Ends At', 'Created'] as $field) {
            $row[$field] = $row[$field] === ''
                ? '—'
                : (new \DateTimeImmutable($row[$field]))->format('F j, Y g:i A');
        }

        return $row;
    }
}

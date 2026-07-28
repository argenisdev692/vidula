<?php

declare(strict_types=1);

namespace Modules\Appointment\Infrastructure\Http\Export;

use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Shared\Domain\Ports\ExportPort;
use Shared\Infrastructure\Support\PhoneFormatter;

/**
 * Maps an {@see AppointmentEloquentModel} row to export columns so CSV, Excel
 * and PDF stay consistent with the on-screen pipeline list. The module ships
 * only this transformer — the writer / streamer / PDF renderer live behind the
 * Shared {@see ExportPort} (BACKEND-PHP §8).
 */
final readonly class AppointmentExportTransformer
{
    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForTable(AppointmentEloquentModel $appointment): array
    {
        return $appointment
            |> self::extractBaseData(...)
            |> self::formatDates(...)
            |> self::sanitizeOutput(...);
    }

    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForPdf(AppointmentEloquentModel $appointment): array
    {
        return self::transformForTable($appointment);
    }

    /**
     * @return array<string, string>
     */
    private static function extractBaseData(AppointmentEloquentModel $appointment): array
    {
        return [
            'Name' => trim("{$appointment->first_name} {$appointment->last_name}"),
            'Company' => $appointment->company_name ?? '—',
            'Email' => $appointment->email,
            'Phone' => PhoneFormatter::national($appointment->phone),
            'Lead Status' => $appointment->status_lead?->value ?? '—',
            'Meeting Status' => $appointment->meeting_status?->value ?? '—',
            'Scheduled At' => $appointment->scheduled_at?->toDateTimeString() ?? '',
            'Created' => $appointment->created_at?->toDateTimeString() ?? '',
            'Status' => $appointment->deleted_at !== null ? 'Suspended' : 'Active',
        ];
    }

    /**
     * Formats `Scheduled At` / `Created` to the mandatory human-readable export
     * format (BACKEND-PHP §8 — "March 3, 2026", never ISO8601/`Y-m-d H:i:s`).
     *
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private static function formatDates(array $row): array
    {
        foreach (['Scheduled At', 'Created'] as $field) {
            $row[$field] = $row[$field] === ''
                ? '—'
                : (new \DateTimeImmutable($row[$field]))->format('F j, Y g:i A');
        }

        return $row;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private static function sanitizeOutput(array $row): array
    {
        return array_map(
            static fn (string $value): string => $value === '' ? '—' : $value,
            $row,
        );
    }
}

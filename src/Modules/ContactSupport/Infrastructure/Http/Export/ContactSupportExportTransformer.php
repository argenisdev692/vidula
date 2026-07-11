<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Infrastructure\Http\Export;

use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;
use Shared\Domain\Ports\ExportPort;

/**
 * Maps a {@see ContactSupportEloquentModel} row to export columns so CSV, Excel
 * and PDF stay consistent with the on-screen inbox. The module ships only this
 * transformer — the writer / streamer / PDF renderer live behind the Shared
 * {@see ExportPort} (BACKEND-PHP §8). The free-text `message`
 * is intentionally omitted (a tabular report carries a scannable summary, not the
 * full body).
 */
final readonly class ContactSupportExportTransformer
{
    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForTable(ContactSupportEloquentModel $contactSupport): array
    {
        return $contactSupport
            |> self::extractBaseData(...)
            |> self::formatDates(...);
    }

    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForPdf(ContactSupportEloquentModel $contactSupport): array
    {
        return self::transformForTable($contactSupport);
    }

    /**
     * @return array<string, string>
     */
    private static function extractBaseData(ContactSupportEloquentModel $contactSupport): array
    {
        return [
            'Name' => trim("{$contactSupport->first_name} {$contactSupport->last_name}"),
            'Email' => $contactSupport->email,
            'Phone' => $contactSupport->phone,
            'Subject' => $contactSupport->subject,
            'Read' => $contactSupport->readed ? 'Read' : 'Unread',
            'SMS Consent' => $contactSupport->sms_consent ? 'Yes' : 'No',
            'Created' => $contactSupport->created_at?->toDateTimeString() ?? '',
            'Status' => $contactSupport->deleted_at !== null ? 'Suspended' : 'Active',
        ];
    }

    /**
     * Formats `Created` to the mandatory human-readable export format
     * (BACKEND-PHP §8 — "March 3, 2026", never ISO8601/`Y-m-d H:i:s`).
     *
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private static function formatDates(array $row): array
    {
        if ($row['Created'] === '') {
            return [...$row, 'Created' => '—'];
        }

        return [...$row, 'Created' => (new \DateTimeImmutable($row['Created']))->format('F j, Y')];
    }
}

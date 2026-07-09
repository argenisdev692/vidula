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
        return [
            'Name' => trim("{$contactSupport->first_name} {$contactSupport->last_name}"),
            'Email' => $contactSupport->email,
            'Phone' => $contactSupport->phone,
            'Subject' => $contactSupport->subject,
            'Read' => $contactSupport->readed ? 'Read' : 'Unread',
            'SMS Consent' => $contactSupport->sms_consent ? 'Yes' : 'No',
            'Created' => $contactSupport->created_at?->toDateTimeString() ?? '—',
            'Status' => $contactSupport->deleted_at !== null ? 'Suspended' : 'Active',
        ];
    }

    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForPdf(ContactSupportEloquentModel $contactSupport): array
    {
        return self::transformForTable($contactSupport);
    }
}

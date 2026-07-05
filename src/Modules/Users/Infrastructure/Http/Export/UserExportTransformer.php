<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Export;

use App\Models\User;

/**
 * Maps a {@see User} row to export columns. The pipe chain (PHP 8.5 `|>`) keeps
 * date/status normalisation consistent across CSV, Excel and PDF.
 *
 * Status is a 3-state lifecycle value (not the §8 2-state default): `Pending`
 * (invited, no password yet) · `Active` · `Suspended` (soft-deleted).
 */
final readonly class UserExportTransformer
{
    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForTable(User $user): array
    {
        return $user
            |> self::extractBaseData(...)
            |> self::formatDates(...);
    }

    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForPdf(User $user): array
    {
        return self::transformForTable($user);
    }

    /**
     * @return array<string, string>
     */
    private static function extractBaseData(User $user): array
    {
        return [
            'Name' => trim("{$user->first_name} {$user->last_name}"),
            'Email' => $user->email,
            'Username' => $user->username ?? '—',
            'Phone' => $user->phone ?? '—',
            'Address 2' => $user->address_2 ?? '—',
            'Status' => self::status($user),
            'Created At' => (string) $user->created_at?->toDateTimeString(),
        ];
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private static function formatDates(array $row): array
    {
        return [...$row, 'Created At' => $row['Created At'] === '' ? '—' : $row['Created At']];
    }

    private static function status(User $user): string
    {
        return match (true) {
            $user->trashed() => 'Suspended',
            $user->isPending() => 'Pending',
            default => 'Active',
        };
    }
}

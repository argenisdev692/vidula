<?php

declare(strict_types=1);

namespace Modules\Clients\Infrastructure\Http\Export;

use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;
use Shared\Infrastructure\Support\PhoneFormatter;

/**
 * Maps a client row to CSV / Excel / PDF columns (BACKEND-PHP §8). Soft-delete
 * Status is Active|Suspended; lifecycle stays in a separate Lifecycle column.
 */
final readonly class ClientExportTransformer
{
    /**
     * @return array{Name: string, Email: string, Phone: string, Lifecycle: string, Owner: string, Created: string, Status: string}
     */
    #[\NoDiscard]
    public static function transformForTable(ClientEloquentModel $client): array
    {
        return $client
            |> self::extractBaseData(...)
            |> self::formatDates(...)
            |> self::sanitizeOutput(...);
    }

    /**
     * @return array{Name: string, Email: string, Phone: string, Lifecycle: string, Owner: string, Created: string, Status: string}
     */
    #[\NoDiscard]
    public static function transformForPdf(ClientEloquentModel $client): array
    {
        return self::transformForTable($client);
    }

    /**
     * @return array{Name: string, Email: string, Phone: string, Lifecycle: string, Owner: string, Created: string, Status: string}
     */
    private static function extractBaseData(ClientEloquentModel $client): array
    {
        return [
            'Name' => $client->client_name,
            'Email' => $client->email ?? '',
            'Phone' => PhoneFormatter::national($client->phone),
            'Lifecycle' => $client->status,
            'Owner' => self::ownerLabel($client),
            'Created' => $client->created_at?->toIso8601String() ?? '',
            'Status' => $client->deleted_at !== null ? 'Suspended' : 'Active',
        ];
    }

    /**
     * @param  array{Name: string, Email: string, Phone: string, Lifecycle: string, Owner: string, Created: string, Status: string}  $data
     * @return array{Name: string, Email: string, Phone: string, Lifecycle: string, Owner: string, Created: string, Status: string}
     */
    private static function formatDates(array $data): array
    {
        if ($data['Created'] !== '') {
            try {
                $data['Created'] = (new \DateTimeImmutable($data['Created']))->format('F j, Y');
            } catch (\Exception) {
                // Keep original value if parsing fails.
            }
        }

        return $data;
    }

    /**
     * @param  array{Name: string, Email: string, Phone: string, Lifecycle: string, Owner: string, Created: string, Status: string}  $data
     * @return array{Name: string, Email: string, Phone: string, Lifecycle: string, Owner: string, Created: string, Status: string}
     */
    private static function sanitizeOutput(array $data): array
    {
        return array_map(
            static fn (string $value): string => $value === '' ? '—' : $value,
            $data,
        );
    }

    private static function ownerLabel(ClientEloquentModel $client): string
    {
        $name = trim(sprintf('%s %s', $client->user?->first_name ?? '', $client->user?->last_name ?? ''));

        return $name !== '' ? $name : 'System';
    }
}

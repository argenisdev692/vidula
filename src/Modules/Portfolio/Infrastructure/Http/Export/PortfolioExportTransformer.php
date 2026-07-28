<?php

declare(strict_types=1);

namespace Modules\Portfolio\Infrastructure\Http\Export;

use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;

/**
 * Maps a portfolio row to CSV / Excel / PDF columns (BACKEND-PHP §8). Soft-delete
 * Status is Active|Suspended; visibility stays in a separate Public column.
 */
final readonly class PortfolioExportTransformer
{
    /**
     * @return array{Title: string, Client: string, Type: string, Tech Stack: string, Public: string, Published: string, Owner: string, Created: string, Status: string}
     */
    #[\NoDiscard]
    public static function transformForTable(PortfolioEloquentModel $portfolio): array
    {
        return $portfolio
            |> self::extractBaseData(...)
            |> self::formatDates(...)
            |> self::sanitizeOutput(...);
    }

    /**
     * @return array{Title: string, Client: string, Type: string, Tech Stack: string, Public: string, Published: string, Owner: string, Created: string, Status: string}
     */
    #[\NoDiscard]
    public static function transformForPdf(PortfolioEloquentModel $portfolio): array
    {
        return self::transformForTable($portfolio);
    }

    /**
     * @return array{Title: string, Client: string, Type: string, Tech Stack: string, Public: string, Published: string, Owner: string, Created: string, Status: string}
     */
    private static function extractBaseData(PortfolioEloquentModel $portfolio): array
    {
        $techStack = is_array($portfolio->tech_stack)
            ? implode(', ', array_values(array_filter(
                $portfolio->tech_stack,
                static fn (mixed $item): bool => is_string($item) && $item !== '',
            )))
            : '';

        return [
            'Title' => $portfolio->title,
            'Client' => $portfolio->client_name,
            'Type' => $portfolio->project_type,
            'Tech Stack' => $techStack,
            'Public' => $portfolio->is_public ? 'Yes' : 'No',
            'Published' => $portfolio->published_at?->toIso8601String() ?? '',
            'Owner' => self::ownerLabel($portfolio),
            'Created' => $portfolio->created_at?->toIso8601String() ?? '',
            'Status' => $portfolio->deleted_at !== null ? 'Suspended' : 'Active',
        ];
    }

    /**
     * @param  array{Title: string, Client: string, Type: string, Tech Stack: string, Public: string, Published: string, Owner: string, Created: string, Status: string}  $data
     * @return array{Title: string, Client: string, Type: string, Tech Stack: string, Public: string, Published: string, Owner: string, Created: string, Status: string}
     */
    private static function formatDates(array $data): array
    {
        foreach (['Published', 'Created'] as $field) {
            if ($data[$field] === '') {
                continue;
            }

            try {
                $data[$field] = (new \DateTimeImmutable($data[$field]))->format('F j, Y');
            } catch (\Exception) {
                // Keep original value if parsing fails.
            }
        }

        return $data;
    }

    /**
     * @param  array{Title: string, Client: string, Type: string, Tech Stack: string, Public: string, Published: string, Owner: string, Created: string, Status: string}  $data
     * @return array{Title: string, Client: string, Type: string, Tech Stack: string, Public: string, Published: string, Owner: string, Created: string, Status: string}
     */
    private static function sanitizeOutput(array $data): array
    {
        return array_map(
            static fn (string $value): string => $value === '' ? '—' : $value,
            $data,
        );
    }

    private static function ownerLabel(PortfolioEloquentModel $portfolio): string
    {
        $name = trim(sprintf('%s %s', $portfolio->user?->first_name ?? '', $portfolio->user?->last_name ?? ''));

        return $name !== '' ? $name : 'System';
    }
}

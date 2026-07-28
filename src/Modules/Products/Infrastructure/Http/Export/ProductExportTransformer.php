<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Export;

use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;

/**
 * Maps a product row to CSV / Excel / PDF columns (BACKEND-PHP §8). Soft-delete
 * Status is Active|Suspended; the catalog lifecycle stays in its own Lifecycle
 * column so the two are never conflated.
 */
final readonly class ProductExportTransformer
{
    /**
     * @return array{Title: string, Type: string, Client: string, Price: string, Lifecycle: string, Level: string, Language: string, Owner: string, Created: string, Status: string}
     */
    #[\NoDiscard]
    public static function transformForExcel(ProductEloquentModel $product): array
    {
        return $product
            |> self::extractBaseData(...)
            |> self::formatDates(...)
            |> self::sanitizeOutput(...);
    }

    /**
     * @return array{Title: string, Type: string, Client: string, Price: string, Lifecycle: string, Level: string, Language: string, Owner: string, Created: string, Status: string}
     */
    #[\NoDiscard]
    public static function transformForPdf(ProductEloquentModel $product): array
    {
        return self::transformForExcel($product);
    }

    /**
     * @return array{Title: string, Type: string, Client: string, Price: string, Lifecycle: string, Level: string, Language: string, Owner: string, Created: string, Status: string}
     */
    private static function extractBaseData(ProductEloquentModel $product): array
    {
        return [
            'Title' => $product->title,
            'Type' => self::humanize(self::scalar($product->type)),
            'Client' => $product->client?->client_name ?? '',
            'Price' => self::priceLabel($product),
            'Lifecycle' => self::humanize(self::scalar($product->status)),
            'Level' => self::humanize($product->level),
            'Language' => strtoupper($product->language),
            'Owner' => self::ownerLabel($product),
            'Created' => $product->created_at?->toIso8601String() ?? '',
            'Status' => $product->deleted_at !== null ? 'Suspended' : 'Active',
        ];
    }

    /**
     * @param  array{Title: string, Type: string, Client: string, Price: string, Lifecycle: string, Level: string, Language: string, Owner: string, Created: string, Status: string}  $data
     * @return array{Title: string, Type: string, Client: string, Price: string, Lifecycle: string, Level: string, Language: string, Owner: string, Created: string, Status: string}
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
     * @param  array{Title: string, Type: string, Client: string, Price: string, Lifecycle: string, Level: string, Language: string, Owner: string, Created: string, Status: string}  $data
     * @return array{Title: string, Type: string, Client: string, Price: string, Lifecycle: string, Level: string, Language: string, Owner: string, Created: string, Status: string}
     */
    private static function sanitizeOutput(array $data): array
    {
        return array_map(
            static fn (string $value): string => $value === '' ? '—' : $value,
            $data,
        );
    }

    private static function priceLabel(ProductEloquentModel $product): string
    {
        return sprintf('%s %s', number_format((float) $product->price, 2, '.', ''), strtoupper($product->currency));
    }

    private static function ownerLabel(ProductEloquentModel $product): string
    {
        $name = trim(sprintf('%s %s', $product->user?->first_name ?? '', $product->user?->last_name ?? ''));

        return $name !== '' ? $name : 'System';
    }

    private static function scalar(mixed $value): string
    {
        return $value instanceof \BackedEnum ? (string) $value->value : (string) $value;
    }

    private static function humanize(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}

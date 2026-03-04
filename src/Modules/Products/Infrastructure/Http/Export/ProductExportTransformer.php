<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Export;

use Modules\Products\Application\Queries\ReadModels\ProductReadModel;

final class ProductExportTransformer
{
    #[\NoDiscard]
    public static function transformForExcel(ProductReadModel $product): array
    {
        return $product
            |> self::extractBaseData(...)
            |> self::formatDates(...)
            |> self::sanitizeOutput(...);
    }

    #[\NoDiscard]
    public static function transformForPdf(ProductReadModel $product): array
    {
        return $product
            |> self::extractPdfData(...)
            |> self::formatDates(...)
            |> self::sanitizeOutput(...);
    }

    private static function extractBaseData(ProductReadModel $product): array
    {
        return [
            'uuid' => $product->id,
            'title' => $product->title,
            'type' => $product->type,
            'price' => $product->price,
            'currency' => $product->currency,
            'level' => $product->level,
            'status' => $product->status,
            'language' => $product->language,
            'created_at' => is_string($product->createdAt) ? $product->createdAt : null,
            'updated_at' => is_string($product->updatedAt) ? $product->updatedAt : null,
        ];
    }

    private static function extractPdfData(ProductReadModel $product): array
    {
        return [
            'title' => $product->title,
            'type' => $product->type,
            'price' => $product->price,
            'currency' => $product->currency,
            'level' => $product->level,
            'status' => $product->status,
            'created_at' => is_string($product->createdAt) ? $product->createdAt : null,
        ];
    }

    /**
     * Format date fields to human-readable format (e.g., "March 3, 2026")
     */
    private static function formatDates(array $data): array
    {
        $dateFields = ['created_at', 'updated_at'];
        
        foreach ($dateFields as $field) {
            if (isset($data[$field]) && is_string($data[$field]) && $data[$field] !== '') {
                try {
                    $date = new \DateTimeImmutable($data[$field]);
                    $data[$field] = $date->format('F j, Y');  // ✅ "March 3, 2026"
                } catch (\Exception) {
                    // Keep original value if parsing fails
                }
            }
        }
        
        return $data;
    }

    private static function sanitizeOutput(array $data): array
    {
        return array_map(fn($value) => $value ?? '', $data);
    }
}

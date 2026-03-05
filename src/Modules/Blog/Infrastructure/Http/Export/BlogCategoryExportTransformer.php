<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Export;

use Modules\Blog\Application\Queries\ReadModels\BlogCategoryListReadModel;
use Modules\Blog\Domain\Entities\BlogCategory;

/**
 * BlogCategoryExportTransformer — Transforms blog category data for exports using pipe operator.
 */
final class BlogCategoryExportTransformer
{
    /**
     * Transform domain entity to export array for Excel
     */
    #[\NoDiscard]
    public static function transformForExcel(BlogCategory $category): array
    {
        return $category
            |> self::extractBaseData(...)
            |> self::formatDates(...)
            |> self::sanitizeOutput(...);
    }

    /**
     * Transform list read model to export array for PDF
     */
    #[\NoDiscard]
    public static function transformForPdf(BlogCategoryListReadModel $category): array
    {
        return $category
            |> self::extractPdfData(...)
            |> self::formatDates(...)
            |> self::sanitizeOutput(...);
    }

    /**
     * Extract base data from domain entity for Excel
     */
    private static function extractBaseData(BlogCategory $category): array
    {
        return [
            'uuid' => $category->uuid,
            'blog_category_name' => $category->name,
            'blog_category_description' => $category->description,
            'blog_category_image' => $category->image,
            'user_id' => $category->userId,
            'created_at' => is_string($category->createdAt) ? $category->createdAt : null,
            'updated_at' => is_string($category->updatedAt) ? $category->updatedAt : null,
            'deleted_at' => is_string($category->deletedAt) ? $category->deletedAt : null,
        ];
    }

    /**
     * Extract data from list read model for PDF export
     */
    private static function extractPdfData(BlogCategoryListReadModel $category): array
    {
        return [
            'uuid' => $category->uuid,
            'blog_category_name' => $category->blogCategoryName,
            'blog_category_description' => $category->blogCategoryDescription,
            'created_at' => $category->createdAt !== '' ? $category->createdAt : null,
        ];
    }

    /**
     * Format date fields to human-readable format (e.g., "March 3, 2026")
     */
    private static function formatDates(array $data): array
    {
        $dateFields = ['created_at', 'updated_at', 'deleted_at'];

        foreach ($dateFields as $field) {
            if (isset($data[$field]) && is_string($data[$field]) && $data[$field] !== '') {
                try {
                    $date = new \DateTimeImmutable($data[$field]);
                    $data[$field] = $date->format('F j, Y');
                } catch (\Exception) {
                    // Keep original value if parsing fails
                }
            }
        }

        return $data;
    }

    /**
     * Sanitize output values (convert null to empty string for display)
     */
    private static function sanitizeOutput(array $data): array
    {
        return array_map(fn($value) => $value ?? '', $data);
    }
}

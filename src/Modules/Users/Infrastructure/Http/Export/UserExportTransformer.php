<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Export;

use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;

/**
 * UserExportTransformer — Transforms user data for exports using pipe operator.
 */
final class UserExportTransformer
{
    /**
     * Transform user model to export array
     */
    #[\NoDiscard]
    public static function transform(UserEloquentModel $user): array
    {
        return $user
            |> self::extractBaseData(...)
            |> self::formatDates(...)
            |> self::sanitizeOutput(...);
    }

    /**
     * Extract base data from user model
     */
    private static function extractBaseData(UserEloquentModel $user): array
    {
        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'name' => $user->name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'username' => $user->username,
            'phone' => $user->phone,
            'city' => $user->city,
            'state' => $user->state,
            'country' => $user->country,
            'created_at' => $user->created_at,
        ];
    }

    /**
     * Format date fields to human-readable format (e.g., "March 3, 2026")
     */
    private static function formatDates(array $data): array
    {
        $dateFields = ['created_at'];
        
        foreach ($dateFields as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                try {
                    // Handle both Carbon instances and strings
                    if ($data[$field] instanceof \DateTimeInterface) {
                        $data[$field] = $data[$field]->format('F j, Y');
                    } elseif (is_string($data[$field])) {
                        $date = new \DateTimeImmutable($data[$field]);
                        $data[$field] = $date->format('F j, Y');
                    }
                } catch (\Exception) {
                    // Keep original value if parsing fails
                }
            }
        }
        
        return $data;
    }

    /**
     * Sanitize output values (convert null to empty string)
     */
    private static function sanitizeOutput(array $data): array
    {
        return array_map(fn($value) => $value ?? '', $data);
    }

    /**
     * Transform for PDF export (simplified format)
     */
    #[\NoDiscard]
    public static function transformForPdf(UserEloquentModel $user): array
    {
        return $user
            |> self::extractPdfData(...)
            |> self::formatDates(...)
            |> self::sanitizeOutput(...);
    }

    /**
     * Extract data specifically for PDF export
     */
    private static function extractPdfData(UserEloquentModel $user): array
    {
        return [
            'uuid' => $user->uuid,
            'name' => $user->name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'city' => $user->city,
            'created_at' => $user->created_at,
        ];
    }
}


<?php

declare(strict_types=1);

namespace Modules\Clients\Infrastructure\Http\Export;

use Modules\Clients\Domain\Entities\Client;

/**
 * ClientExportTransformer — Transforms client data for exports using pipe operator.
 */
final class ClientExportTransformer
{
    /**
     * Transform client entity to export array for Excel
     */
    #[\NoDiscard]
    public static function transformForExcel(Client $client): array
    {
        return $client
            |> self::extractBaseData(...)
            |> self::formatDates(...)
            |> self::sanitizeOutput(...);
    }

    /**
     * Transform client entity to export array for PDF
     */
    #[\NoDiscard]
    public static function transformForPdf(Client $client): array
    {
        return $client
            |> self::extractPdfData(...)
            |> self::formatDates(...)
            |> self::sanitizeOutput(...);
    }

    /**
     * Extract base data from client entity for Excel
     */
    private static function extractBaseData(Client $client): array
    {
        return [
            'id' => $client->id->value,
            'uuid' => $client->id->value,
            'company_name' => $client->companyName,
            'email' => $client->email,
            'phone' => $client->phone,
            'address' => $client->address,
            'website' => $client->socialLinks?->website,
            'facebook' => $client->socialLinks?->facebook,
            'instagram' => $client->socialLinks?->instagram,
            'linkedin' => $client->socialLinks?->linkedin,
            'twitter' => $client->socialLinks?->twitter,
            'latitude' => $client->coordinates?->latitude,
            'longitude' => $client->coordinates?->longitude,
            'created_at' => $client->createdAt,
            'updated_at' => $client->updatedAt,
        ];
    }

    /**
     * Extract data specifically for PDF export
     */
    private static function extractPdfData(Client $client): array
    {
        return [
            'uuid' => $client->id->value,
            'company_name' => $client->companyName,
            'email' => $client->email,
            'phone' => $client->phone,
            'address' => $client->address,
            'website' => $client->socialLinks?->website,
            'created_at' => $client->createdAt,
        ];
    }

    /**
     * Format date fields
     */
    private static function formatDates(array $data): array
    {
        // Dates are already in ISO8601 format from domain entity
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


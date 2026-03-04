<?php

declare(strict_types=1);

namespace Modules\Clients\Infrastructure\Http\Export;

use Modules\Clients\Application\Queries\ReadModels\ClientReadModel;

/**
 * ClientExportTransformer — Transforms client data for exports using pipe operator.
 */
final class ClientExportTransformer
{
    /**
     * Transform client entity to export array for Excel
     */
    #[\NoDiscard]
    public static function transformForExcel(ClientReadModel $client): array
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
    public static function transformForPdf(ClientReadModel $client): array
    {
        return $client
            |> self::extractPdfData(...)
            |> self::formatDates(...)
            |> self::sanitizeOutput(...);
    }

    /**
     * Extract base data from array for Excel
     */
    private static function extractBaseData(ClientReadModel $client): array
    {
        return [
            'id' => $client->uuid,
            'uuid' => $client->uuid,
            'client_name' => $client->clientName,
            'email' => $client->email,
            'phone' => $client->phone,
            'address' => $client->address,
            'website' => $client->socialLinks['website'] ?? null,
            'facebook' => $client->socialLinks['facebook'] ?? null,
            'instagram' => $client->socialLinks['instagram'] ?? null,
            'linkedin' => $client->socialLinks['linkedin'] ?? null,
            'twitter' => $client->socialLinks['twitter'] ?? null,
            'latitude' => $client->coordinates['latitude'] ?? null,
            'longitude' => $client->coordinates['longitude'] ?? null,
            'created_at' => is_string($client->createdAt) ? $client->createdAt : null,
            'updated_at' => is_string($client->updatedAt) ? $client->updatedAt : null,
        ];
    }

    /**
     * Extract data specifically for PDF export
     */
    private static function extractPdfData(ClientReadModel $client): array
    {
        return [
            'uuid' => $client->uuid,
            'client_name' => $client->clientName,
            'email' => $client->email,
            'phone' => $client->phone,
            'address' => $client->address,
            'website' => $client->socialLinks['website'] ?? null,
            'created_at' => is_string($client->createdAt) ? $client->createdAt : null,
        ];
    }

    /**
     * Format date fields
     */
    private static function formatDates(array $data): array
    {
        // Dates are already in ISO8601 format from domain entity or readmodel
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

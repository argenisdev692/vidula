<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Services;

use Modules\Clients\Application\Queries\ReadModels\ClientReadModel;

/**
 * ClientDataTransformer - Using PHP 8.5 Pipe Operator
 *
 * Transforms client data through a pipeline of operations
 */
final readonly class ClientDataTransformer
{
    /**
     * Transform client data using PHP 8.5 pipe operator
     */
    public static function transformForExport(ClientReadModel $client): array
    {
        return $client
            |> self::extractBaseData(...)
            |> self::addFormattedDates(...)
            |> self::addSocialLinks(...)
            |> self::addCoordinates(...);
    }

    /**
     * Extract base client data
     */
    private static function extractBaseData(ClientReadModel $client): array
    {
        return [
            'uuid' => $client->uuid,
            'client_name' => $client->clientName,
            'email' => $client->email ?? 'N/A',
            'phone' => $client->phone ?? 'N/A',
            'address' => $client->address ?? 'N/A',
        ];
    }

    /**
     * Add formatted dates to the data
     */
    private static function addFormattedDates(array $data): array
    {
        return [
            ...$data,
            'created_at' => $data['created_at'] ?? now()->toIso8601String(),
            'updated_at' => $data['updated_at'] ?? now()->toIso8601String(),
        ];
    }

    /**
     * Add social links to the data
     */
    private static function addSocialLinks(array $data): array
    {
        return [
            ...$data,
            'social_links' => [
                'website' => $data['website'] ?? null,
                'facebook' => $data['facebook'] ?? null,
                'instagram' => $data['instagram'] ?? null,
                'linkedin' => $data['linkedin'] ?? null,
                'twitter' => $data['twitter'] ?? null,
            ],
        ];
    }

    /**
     * Add coordinates to the data
     */
    private static function addCoordinates(array $data): array
    {
        return [
            ...$data,
            'coordinates' => [
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ],
        ];
    }

    /**
     * Sanitize client input using pipe operator
     */
    #[\NoDiscard]
    public static function sanitizeInput(array $input): array
    {
        return $input
            |> self::trimStrings(...)
            |> self::normalizeUrls(...)
            |> self::validateCoordinates(...);
    }

    private static function trimStrings(array $data): array
    {
        return array_map(
            fn($value) => is_string($value) ? trim($value) : $value,
            $data
        );
    }

    private static function normalizeUrls(array $data): array
    {
        $urlFields = ['website', 'facebook_link', 'instagram_link', 'linkedin_link', 'twitter_link'];

        foreach ($urlFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                try {
                    filter_var($data[$field], FILTER_VALIDATE_URL, FILTER_THROW_ON_FAILURE);
                } catch (\ValueError) {
                    $data[$field] = null;
                    continue;
                }
            }
        }

        return $data;
    }

    private static function validateCoordinates(array $data): array
    {
        if (isset($data['latitude'])) {
            $data['latitude'] = max(-90, min(90, (float) $data['latitude']));
        }

        if (isset($data['longitude'])) {
            $data['longitude'] = max(-180, min(180, (float) $data['longitude']));
        }

        return $data;
    }
}

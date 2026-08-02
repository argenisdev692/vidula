<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Support;

use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;

/**
 * Frozen client billing fields copied onto an invoice at create/update.
 */
final class InvoiceClientSnapshot
{
    /**
     * @return array{
     *     client_name: string,
     *     client_tax_id: string|null,
     *     client_address: string|null,
     *     client_city: string|null,
     *     client_country: string|null,
     *     client_country_code: string|null
     * }
     */
    public static function fromClient(ClientEloquentModel $client): array
    {
        $countryCode = $client->country_code !== null && $client->country_code !== ''
            ? strtoupper($client->country_code)
            : null;

        return [
            'client_name' => $client->client_name,
            'client_tax_id' => $client->tax_id ?? $client->nif,
            'client_address' => $client->address,
            'client_city' => null,
            'client_country' => $client->country,
            'client_country_code' => $countryCode,
        ];
    }
}

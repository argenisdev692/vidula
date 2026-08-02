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
     *     client_email: string|null,
     *     client_phone: string|null,
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
            'client_tax_id' => self::resolveTaxId($client->tax_id, $client->nif),
            'client_email' => $client->email,
            'client_phone' => $client->phone,
            'client_address' => $client->address,
            'client_city' => null,
            'client_country' => $client->country,
            'client_country_code' => $countryCode,
        ];
    }

    private static function resolveTaxId(?string $taxId, ?string $nif): ?string
    {
        $tax = trim((string) $taxId);
        if ($tax !== '' && $tax !== '0') {
            return $tax;
        }

        $nifValue = trim((string) $nif);
        if ($nifValue !== '' && $nifValue !== '0') {
            return $nifValue;
        }

        return null;
    }
}

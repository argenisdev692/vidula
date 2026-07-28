<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Support;

use Modules\Invoices\Application\DTOs\InvoiceData;
use Modules\Invoices\Application\DTOs\InvoiceItemData;

/**
 * Shared totals / item mapping for create and update invoice handlers.
 */
final readonly class InvoiceTotalsCalculator
{
    /**
     * @param  array<string, int>  $serviceIdsByUuid
     * @return array{subtotal: float, tax_amount: float, total: float, items: list<array<string, mixed>>}
     */
    #[\NoDiscard]
    public static function compute(InvoiceData $data, array $serviceIdsByUuid = []): array
    {
        $items = [];
        $subtotal = 0.0;

        foreach ($data->items as $index => $item) {
            if (! $item instanceof InvoiceItemData) {
                $item = InvoiceItemData::from($item);
            }

            $quantity = round($item->quantity, 2);
            $unitPrice = round($item->unitPrice, 2);
            $amount = round($quantity * $unitPrice, 2);
            $subtotal += $amount;

            $serviceId = null;
            if ($item->serviceUuid !== null && isset($serviceIdsByUuid[$item->serviceUuid])) {
                $serviceId = $serviceIdsByUuid[$item->serviceUuid];
            }

            $items[] = [
                'service_id' => $serviceId,
                'sort_order' => $item->sortOrder > 0 ? $item->sortOrder : $index,
                'title' => $item->title,
                'description' => $item->description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $amount,
            ];
        }

        $subtotal = round($subtotal, 2);
        $taxAmount = 0.0;

        if ($data->taxMode === 'PERCENT') {
            $rate = $data->taxRate ?? 0.0;
            $taxAmount = round($subtotal * ($rate / 100), 2);
        }

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => round($subtotal + $taxAmount, 2),
            'items' => $items,
        ];
    }

    /**
     * @param  list<InvoiceItemData|array<string, mixed>>  $items
     * @return list<string>
     */
    public static function collectServiceUuids(array $items): array
    {
        $uuids = [];

        foreach ($items as $item) {
            if (! $item instanceof InvoiceItemData) {
                $item = InvoiceItemData::from($item);
            }

            if ($item->serviceUuid !== null) {
                $uuids[] = $item->serviceUuid;
            }
        }

        return array_values(array_unique($uuids));
    }

    /**
     * Parse `007/2026` into sequence + year.
     *
     * @return array{sequence: int, year: int}
     */
    public static function parseInvoiceNumber(string $invoiceNumber): array
    {
        [$sequence, $year] = explode('/', $invoiceNumber, 2);

        return [
            'sequence' => (int) $sequence,
            'year' => (int) $year,
        ];
    }

    #[\NoDiscard]
    public static function formatInvoiceNumber(int $sequence, int $year): string
    {
        return sprintf('%03d/%d', $sequence, $year);
    }
}

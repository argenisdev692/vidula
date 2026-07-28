<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Invoices\Application\DTOs\InvoiceData;
use Modules\Invoices\Application\Support\InvoiceCacheKeys;
use Modules\Invoices\Application\Support\InvoiceTotalsCalculator;
use Modules\Invoices\Domain\Ports\InvoiceRepositoryPort;
use Modules\Invoices\Infrastructure\Persistence\Eloquent\Models\InvoiceEloquentModel;
use Modules\Invoices\Infrastructure\Queue\GenerateInvoicePdfJob;

final readonly class CreateInvoiceHandler
{
    public function __construct(
        private InvoiceRepositoryPort $invoices,
        private ClientRepositoryPort $clients,
        private Cache $cache,
    ) {}

    #[\NoDiscard]
    public function handle(InvoiceData $data, int $userId): InvoiceEloquentModel
    {
        $client = $this->clients->findByUuid($data->clientUuid)
            ?? throw ValidationException::withMessages([
                'client_uuid' => [__('The selected client is invalid.')],
            ]);

        $parsed = InvoiceTotalsCalculator::parseInvoiceNumber($data->invoiceNumber);
        $serviceIds = $this->invoices->mapServiceIdsByUuid(
            InvoiceTotalsCalculator::collectServiceUuids($data->items),
        );
        $productId = $this->invoices->findProductIdByUuid($data->productUuid);
        $totals = InvoiceTotalsCalculator::compute($data, $serviceIds);

        if ($this->invoices->numberExists($data->invoiceNumber, $parsed['year'], $parsed['sequence'])) {
            throw ValidationException::withMessages([
                'invoice_number' => [__('This invoice number is already used for that year.')],
            ]);
        }

        $invoice = DB::transaction(fn () => $this->invoices->createWithItems([
            'user_id' => $userId,
            'client_id' => $client->id,
            'product_id' => $productId,
            'invoice_number' => $data->invoiceNumber,
            'sequence' => $parsed['sequence'],
            'year' => $parsed['year'],
            'issue_date' => $data->issueDate,
            'due_date' => $data->dueDate,
            'currency' => $data->currency,
            'tax_mode' => $data->taxMode,
            'tax_rate' => $data->taxMode === 'PERCENT' ? ($data->taxRate ?? 0.0) : null,
            'tax_label' => $data->taxLabel,
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['tax_amount'],
            'total' => $totals['total'],
            'is_paid' => $data->isPaid,
            'payment_method' => $data->isPaid ? $data->paymentMethod : null,
            'transfer_number' => $data->isPaid ? $data->transferNumber : null,
            'payment_date' => $data->isPaid ? $data->paymentDate : null,
            'amount_received' => $data->isPaid ? $data->amountReceived : null,
            'notes' => $data->notes,
            'client_name' => $client->client_name,
            'client_tax_id' => $client->tax_id ?? $client->nif,
            'client_address' => $client->address,
            'client_city' => null,
            'client_country' => null,
        ], $totals['items']));

        $this->cache->forget(InvoiceCacheKeys::invoice($invoice->uuid));
        $this->cache->forget(InvoiceCacheKeys::pdf($invoice->uuid));
        GenerateInvoicePdfJob::dispatch($invoice->uuid)->afterCommit();

        return $invoice;
    }
}

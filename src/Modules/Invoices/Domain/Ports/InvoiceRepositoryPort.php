<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Ports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Invoices\Application\DTOs\InvoiceFilterData;
use Modules\Invoices\Infrastructure\Persistence\Eloquent\Models\InvoiceEloquentModel;

interface InvoiceRepositoryPort
{
    public function paginate(InvoiceFilterData $filters, int $perPage): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?InvoiceEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<array<string, mixed>>  $items
     */
    public function createWithItems(array $attributes, array $items): InvoiceEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<array<string, mixed>>  $items
     */
    public function updateWithItems(InvoiceEloquentModel $invoice, array $attributes, array $items): InvoiceEloquentModel;

    public function softDelete(string $uuid): bool;

    public function restore(string $uuid): bool;

    /**
     * @param  list<string>  $uuids
     */
    public function bulkSoftDeleteByUuid(array $uuids): int;

    /**
     * @param  list<string>  $uuids
     */
    public function bulkRestoreByUuid(array $uuids): int;

    public function nextSequenceForYear(int $year): int;

    public function numberExists(
        string $invoiceNumber,
        int $year,
        int $sequence,
        ?string $exceptUuid = null,
    ): bool;

    /**
     * @param  list<string>  $serviceUuids
     * @return array<string, int>
     */
    public function mapServiceIdsByUuid(array $serviceUuids): array;

    public function findProductIdByUuid(?string $productUuid): ?int;

    /**
     * @return list<array{uuid: string, client_name: string, tax_id: string|null, nif: string|null, address: string|null, email: string|null}>
     */
    public function listActiveClientsForForm(int $limit = 200): array;

    /**
     * @return list<array{uuid: string, title: string, description: string|null, price: mixed, currency: string, type: string}>
     */
    public function listPublishedProductsForForm(int $limit = 200): array;

    public function defaultInvoiceNotes(): ?string;
}

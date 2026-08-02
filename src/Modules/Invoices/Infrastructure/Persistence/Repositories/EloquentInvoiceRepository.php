<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Persistence\Repositories;

use App\Models\CompanyData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;
use Modules\Invoices\Application\DTOs\InvoiceFilterData;
use Modules\Invoices\Domain\Ports\InvoiceRepositoryPort;
use Modules\Invoices\Infrastructure\Persistence\Eloquent\Models\InvoiceEloquentModel;
use Modules\Products\Domain\Enums\ProductStatus;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Services\Infrastructure\Persistence\Eloquent\Models\ServiceEloquentModel;
use Shared\Infrastructure\Persistence\Concerns\BulkSoftDeletesByUuid;

final class EloquentInvoiceRepository implements InvoiceRepositoryPort
{
    use BulkSoftDeletesByUuid;

    protected function model(): string
    {
        return InvoiceEloquentModel::class;
    }

    public function paginate(InvoiceFilterData $filters, int $perPage): LengthAwarePaginator
    {
        return InvoiceEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->with([
                'client:id,uuid,client_name',
                'user:id,first_name,last_name',
                'product:id,uuid,title,type,currency',
            ])
            ->select([
                'id',
                'uuid',
                'user_id',
                'client_id',
                'product_id',
                'invoice_number',
                'sequence',
                'year',
                'issue_date',
                'due_date',
                'currency',
                'tax_mode',
                'tax_rate',
                'tax_label',
                'subtotal',
                'tax_amount',
                'total',
                'is_paid',
                'created_at',
                'deleted_at',
            ])
            ->orderByDesc('issue_date')
            ->orderByDesc('sequence')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByUuid(string $uuid): ?InvoiceEloquentModel
    {
        return InvoiceEloquentModel::withTrashed()
            ->with([
                'client:id,uuid,client_name,email,phone,tax_id,nif,address,country,country_code',
                'user:id,first_name,last_name',
                'product:id,uuid,title,description,price,currency,type',
                'items.service:id,uuid,name,description',
            ])
            ->where('uuid', $uuid)
            ->first();
    }

    public function createWithItems(array $attributes, array $items): InvoiceEloquentModel
    {
        $invoice = InvoiceEloquentModel::query()->create($attributes);
        $invoice->items()->createMany($items);

        return $invoice->load(['client:id,uuid,client_name', 'product:id,uuid,title,type', 'items']);
    }

    public function updateWithItems(InvoiceEloquentModel $invoice, array $attributes, array $items): InvoiceEloquentModel
    {
        $invoice->update($attributes);
        $invoice->items()->delete();
        $invoice->items()->createMany($items);

        return $invoice->refresh()->load(['client:id,uuid,client_name', 'product:id,uuid,title,type', 'items']);
    }

    public function softDelete(string $uuid): bool
    {
        return (bool) InvoiceEloquentModel::query()->where('uuid', $uuid)->delete();
    }

    public function restore(string $uuid): bool
    {
        return (bool) InvoiceEloquentModel::onlyTrashed()->where('uuid', $uuid)->restore();
    }

    public function nextSequenceForYear(int $year): int
    {
        $max = InvoiceEloquentModel::withTrashed()
            ->where('year', $year)
            ->max('sequence');

        return ((int) $max) + 1;
    }

    public function numberExists(
        string $invoiceNumber,
        int $year,
        int $sequence,
        ?string $exceptUuid = null,
    ): bool {
        return InvoiceEloquentModel::withTrashed()
            ->when(
                $exceptUuid !== null,
                fn ($q) => $q->where('uuid', '!=', $exceptUuid),
            )
            ->where(function ($q) use ($invoiceNumber, $year, $sequence): void {
                $q->where('invoice_number', $invoiceNumber)
                    ->orWhere(fn ($w) => $w->where('year', $year)->where('sequence', $sequence));
            })
            ->exists();
    }

    public function mapServiceIdsByUuid(array $serviceUuids): array
    {
        if ($serviceUuids === []) {
            return [];
        }

        return ServiceEloquentModel::query()
            ->whereIn('uuid', array_values(array_unique($serviceUuids)))
            ->pluck('id', 'uuid')
            ->all();
    }

    public function findProductIdByUuid(?string $productUuid): ?int
    {
        if ($productUuid === null || $productUuid === '') {
            return null;
        }

        $id = ProductEloquentModel::query()
            ->where('uuid', $productUuid)
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    public function listActiveClientsForForm(int $limit = 200): array
    {
        return ClientEloquentModel::query()
            ->where('status', 'ACTIVE')
            ->orderBy('client_name')
            ->select(['uuid', 'client_name', 'tax_id', 'nif', 'address', 'email', 'country', 'country_code'])
            ->limit($limit)
            ->get()
            ->map(static fn (ClientEloquentModel $client): array => [
                'uuid' => $client->uuid,
                'client_name' => $client->client_name,
                'tax_id' => $client->tax_id,
                'nif' => $client->nif,
                'address' => $client->address,
                'email' => $client->email,
                'country' => $client->country,
                'country_code' => $client->country_code,
            ])
            ->values()
            ->all();
    }

    public function listPublishedProductsForForm(int $limit = 200): array
    {
        return ProductEloquentModel::query()
            ->where('status', ProductStatus::Published)
            ->orderBy('title')
            ->select(['uuid', 'title', 'description', 'price', 'currency', 'type'])
            ->limit($limit)
            ->get()
            ->map(static fn (ProductEloquentModel $product): array => [
                'uuid' => $product->uuid,
                'title' => $product->title,
                'description' => $product->description,
                'price' => $product->price,
                'currency' => $product->currency,
                'type' => $product->type instanceof \BackedEnum
                    ? $product->type->value
                    : (string) $product->type,
            ])
            ->values()
            ->all();
    }

    public function defaultInvoiceNotes(): ?string
    {
        $notes = CompanyData::query()->orderBy('id')->value('invoice_notes');

        return is_string($notes) ? $notes : null;
    }
}

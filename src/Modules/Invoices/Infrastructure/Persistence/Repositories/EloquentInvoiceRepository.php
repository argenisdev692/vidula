<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Persistence\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Invoices\Application\DTOs\InvoiceFilterData;
use Modules\Invoices\Domain\Ports\InvoiceRepositoryPort;
use Modules\Invoices\Infrastructure\Persistence\Eloquent\Models\InvoiceEloquentModel;
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
            ])
            ->select([
                'id',
                'uuid',
                'user_id',
                'client_id',
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
                'client_name',
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
                'client:id,uuid,client_name,email,tax_id,nif,address',
                'user:id,first_name,last_name',
                'items.service:id,uuid,name,description',
            ])
            ->where('uuid', $uuid)
            ->first();
    }

    public function createWithItems(array $attributes, array $items): InvoiceEloquentModel
    {
        $invoice = InvoiceEloquentModel::query()->create($attributes);
        $invoice->items()->createMany($items);

        return $invoice->load(['client:id,uuid,client_name', 'items']);
    }

    public function updateWithItems(InvoiceEloquentModel $invoice, array $attributes, array $items): InvoiceEloquentModel
    {
        $invoice->update($attributes);
        $invoice->items()->delete();
        $invoice->items()->createMany($items);

        return $invoice->refresh()->load(['client:id,uuid,client_name', 'items']);
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
}

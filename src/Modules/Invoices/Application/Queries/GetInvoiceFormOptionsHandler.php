<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Queries;

use Modules\Invoices\Domain\Ports\InvoiceRepositoryPort;
use Modules\Services\Domain\Ports\ServiceRepositoryPort;

/**
 * Catalog props for the invoice create form (clients, services, products, notes).
 */
final readonly class GetInvoiceFormOptionsHandler
{
    public function __construct(
        private InvoiceRepositoryPort $invoices,
        private ServiceRepositoryPort $services,
    ) {}

    /**
     * @return array{
     *     clients: list<array{uuid: string, client_name: string, tax_id: string|null, nif: string|null, address: string|null, email: string|null}>,
     *     services: list<array{uuid: string, name: string, description: string|null}>,
     *     products: list<array{uuid: string, title: string, description: string|null, price: mixed, currency: string, type: string}>,
     *     defaultNotes: string|null
     * }
     */
    public function handle(): array
    {
        return [
            'clients' => $this->invoices->listActiveClientsForForm(),
            'services' => $this->services->listActive()->map(static fn ($service): array => [
                'uuid' => $service->uuid,
                'name' => $service->name,
                'description' => $service->description,
            ])->values()->all(),
            'products' => $this->invoices->listPublishedProductsForForm(),
            'defaultNotes' => $this->invoices->defaultInvoiceNotes(),
        ];
    }
}

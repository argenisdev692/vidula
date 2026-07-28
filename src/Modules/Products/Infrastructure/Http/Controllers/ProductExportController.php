<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Products\Application\DTOs\ProductFilterData;
use Modules\Products\Infrastructure\Http\Export\ProductExportTransformer;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Shared\Domain\Ports\ExportPort;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams filtered products as CSV / XLSX / PDF. Reuses ProductFilterData +
 * scopeApplyFilters (DRY) and Shared ExportPort.
 */
final readonly class ProductExportController
{
    public function __construct(private ExportPort $export) {}

    public function __invoke(Request $request): StreamedResponse|Response
    {
        $format = (string) $request->string('format', 'csv');
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 422);

        $filters = ProductFilterData::validateAndCreate($request);

        $rows = ProductEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->with(['user:id,first_name,last_name', 'client:id,uuid,client_name'])
            ->orderBy($filters->resolvedSortField(), $filters->resolvedSortDirection())
            ->lazy();

        return match ($format) {
            'pdf' => $this->export->pdf(
                'products.pdf',
                'exports.pdf.products',
                [
                    'rows' => $rows->map(ProductExportTransformer::transformForPdf(...)),
                    'generatedAt' => now()->format('F j, Y H:i'),
                ],
            ),
            default => $this->export->tabular(
                "products.{$format}",
                ['Title', 'Type', 'Client', 'Price', 'Lifecycle', 'Level', 'Language', 'Owner', 'Created', 'Status'],
                $rows->map(ProductExportTransformer::transformForExcel(...)),
            ),
        };
    }
}

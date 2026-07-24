<?php

declare(strict_types=1);

namespace Modules\Clients\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Clients\Application\DTOs\ClientFilterData;
use Modules\Clients\Infrastructure\Http\Export\ClientExportTransformer;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;
use Shared\Domain\Ports\ExportPort;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams filtered clients as CSV / XLSX / PDF. Reuses ClientFilterData +
 * scopeApplyFilters (DRY) and Shared ExportPort.
 */
final readonly class ClientExportController
{
    public function __construct(private ExportPort $export) {}

    public function __invoke(Request $request): StreamedResponse|Response
    {
        $format = (string) $request->string('format', 'csv');
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 422);

        $filters = ClientFilterData::validateAndCreate($request);

        $rows = ClientEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->with('user:id,first_name,last_name')
            ->orderByDesc('created_at')
            ->lazy();

        return match ($format) {
            'pdf' => $this->export->pdf(
                'clients.pdf',
                'exports.pdf.clients',
                [
                    'rows' => $rows->map(ClientExportTransformer::transformForPdf(...)),
                    'generatedAt' => now()->format('F j, Y H:i'),
                ],
            ),
            default => $this->export->tabular(
                "clients.{$format}",
                ['Name', 'Email', 'Phone', 'Lifecycle', 'Owner', 'Created', 'Status'],
                $rows->map(ClientExportTransformer::transformForTable(...)),
            ),
        };
    }
}

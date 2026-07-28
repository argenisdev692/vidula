<?php

declare(strict_types=1);

namespace Modules\Portfolio\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Portfolio\Application\DTOs\PortfolioFilterData;
use Modules\Portfolio\Infrastructure\Http\Export\PortfolioExportTransformer;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;
use Shared\Domain\Ports\ExportPort;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams filtered portfolios as CSV / XLSX / PDF. Reuses PortfolioFilterData +
 * scopeApplyFilters (DRY) and Shared ExportPort.
 */
final readonly class PortfolioExportController
{
    public function __construct(private ExportPort $export) {}

    public function __invoke(Request $request): StreamedResponse|Response
    {
        $format = (string) $request->string('format', 'csv');
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 422);

        $filters = PortfolioFilterData::validateAndCreate($request);

        $rows = PortfolioEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->with('user:id,first_name,last_name')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->lazy();

        return match ($format) {
            'pdf' => $this->export->pdf(
                'portfolios.pdf',
                'exports.pdf.portfolios',
                [
                    'rows' => $rows->map(PortfolioExportTransformer::transformForPdf(...)),
                    'generatedAt' => now()->format('F j, Y H:i'),
                ],
            ),
            default => $this->export->tabular(
                "portfolios.{$format}",
                ['Title', 'Client', 'Type', 'Tech Stack', 'Public', 'Published', 'Owner', 'Created', 'Status'],
                $rows->map(PortfolioExportTransformer::transformForTable(...)),
            ),
        };
    }
}

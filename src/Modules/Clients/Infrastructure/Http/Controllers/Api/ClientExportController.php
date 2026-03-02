<?php

declare(strict_types=1);

namespace Modules\Clients\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Clients\Application\DTOs\ClientFilterDTO;
use Modules\Clients\Application\Queries\ListClient\ListClientHandler;
use Modules\Clients\Application\Queries\ListClient\ListClientQuery;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Clients\Infrastructure\Http\Export\ClientExcelExport;
use Modules\Clients\Infrastructure\Http\Export\ClientPdfExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * ClientExportController — Handles client data exports
 * 
 * Follows hexagonal architecture by injecting repository and handler.
 */
final class ClientExportController
{
    public function __construct(
        private readonly ClientRepositoryPort $repository,
        private readonly ListClientHandler $handler
    ) {
    }

    public function __invoke(Request $request): Response|BinaryFileResponse
    {
        $filters = ClientFilterDTO::from($request->all());
        $format = $request->query('format', 'excel');

        return match ($format) {
            'excel' => $this->exportExcel($filters),
            'pdf' => $this->exportPdf($filters),
            default => response()->json(['error' => 'Invalid format. Use "excel" or "pdf"'], 422),
        };
    }

    /**
     * Export to Excel format
     */
    private function exportExcel(ClientFilterDTO $filters): BinaryFileResponse
    {
        $export = new ClientExcelExport($filters, $this->repository);
        
        return Excel::download(
            $export,
            'clients-export-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Export to PDF format
     */
    private function exportPdf(ClientFilterDTO $filters): Response
    {
        $query = new ListClientQuery($filters);
        $pdfExport = new ClientPdfExport($this->handler, $query);
        
        return $pdfExport->stream();
    }
}


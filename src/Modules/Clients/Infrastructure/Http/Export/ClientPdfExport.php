<?php

declare(strict_types=1);

namespace Modules\Clients\Infrastructure\Http\Export;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Modules\Clients\Application\Queries\ListClient\ListClientHandler;
use Modules\Clients\Application\Queries\ListClient\ListClientQuery;

final class ClientPdfExport
{
    public function __construct(
        private readonly ListClientHandler $handler,
        private readonly ListClientQuery $query
    ) {
    }

    public function stream(): Response
    {
        $result = $this->handler->handle($this->query);
        
        // Transform clients using pipe operator and transformer
        $rows = $result['data']
            |> (fn($clients) => array_map(ClientExportTransformer::transformForPdf(...), $clients));

        $pdf = Pdf::loadView('exports.pdf.clients', [
            'title' => 'Client Directory Report',
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'rows' => $rows,
        ]);

        return $pdf->stream('client-report-' . now()->format('Y-m-d') . '.pdf');
    }
}

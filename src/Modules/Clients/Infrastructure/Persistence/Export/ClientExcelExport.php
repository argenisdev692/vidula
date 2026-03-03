<?php
declare(strict_types=1);
namespace Modules\Clients\Infrastructure\Persistence\Export;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Modules\Clients\Application\Queries\ListClient\ListClientHandler;
use Modules\Clients\Application\Queries\ListClient\ListClientQuery;

final class ClientExcelExport implements FromView
{
    public function __construct(
        private readonly ListClientHandler $handler,
        private readonly ListClientQuery $query
    ) {
    }

    public function view(): View
    {
        $result = $this->handler->handle($this->query);
        $rows = array_map(
            \Modules\Clients\Infrastructure\Http\Export\ClientExportTransformer::transformForExcel(...),
            $result['data']
        );

        return view('exports.pdf.clients', [
            'rows' => $rows,
            'title' => 'Client Directory Report',
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}

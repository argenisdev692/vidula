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
        return view('exports.pdf.clients', ['items' => $this->handler->handle($this->query)['data']]);
    }
}

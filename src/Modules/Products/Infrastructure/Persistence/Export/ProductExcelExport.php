<?php
declare(strict_types=1);
namespace Modules\Products\Infrastructure\Persistence\Export;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Modules\Products\Application\Queries\ListProduct\ListProductHandler;
use Modules\Products\Application\Queries\ListProduct\ListProductQuery;

final class ProductExcelExport implements FromView
{
    public function __construct(
        private readonly ListProductHandler $handler,
        private readonly ListProductQuery $query
    ) {
    }

    public function view(): View
    {
        return view('exports.pdf.products', ['items' => $this->handler->handle($this->query)['data']]);
    }
}

<?php
declare(strict_types=1);
namespace Modules\Products\Infrastructure\Persistence\Export;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Products\Application\Queries\ListProduct\ListProductHandler;
use Modules\Products\Application\Queries\ListProduct\ListProductQuery;

final class ProductPdfExport
{
    public function __construct(
        private readonly ListProductHandler $handler,
        private readonly ListProductQuery $query
    ) {
    }

    public function export(): mixed
    {
        $pdf = Pdf::loadView('exports.pdf.products', ['items' => $this->handler->handle($this->query)['data']]);
        return $pdf->download('products.pdf');
    }
}

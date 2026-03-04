<?php
declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Export;

use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Products\Application\DTOs\ProductFilterDTO;
use Modules\Products\Application\Queries\ListProduct\ListProductHandler;
use Modules\Products\Application\Queries\ListProduct\ListProductQuery;

final class ProductPdfExport
{
    public function __construct(
        private readonly ListProductHandler $handler,
        private readonly ProductFilterDTO $filters
    ) {}

    public function export(): mixed
    {
        $query = new ListProductQuery($this->filters);
        $result = $this->handler->handle($query);
        
        $rows = array_map(
            fn($product) => ProductExportTransformer::transformForPdf($product),
            $result['data']
        );

        $pdf = Pdf::loadView('exports.pdf.products', [
            'rows' => $rows,
            'generatedAt' => now()->format('F j, Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('products-export.pdf');
    }
}

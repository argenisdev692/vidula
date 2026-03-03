<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Controllers\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Products\Application\DTOs\ProductFilterDTO;
use Modules\Products\Infrastructure\Persistence\Export\ProductExcelExport;
use Modules\Products\Infrastructure\Persistence\Export\ProductPdfExport;
use Modules\Products\Application\Queries\ListProduct\ListProductHandler;
use Modules\Products\Application\Queries\ListProduct\ListProductQuery;

final class ProductExportController
{
    public function __construct(
        private readonly ListProductHandler $listHandler
    ) {
    }

    public function __invoke(Request $request): mixed
    {
        $filters = ProductFilterDTO::from($request->all());
        $format = $request->query('format', 'excel');
        $query = new ListProductQuery($filters);

        if ($format === 'pdf') {
            $pdfExport = new ProductPdfExport($this->listHandler, $query);
            return $pdfExport->export();
        }

        $excelExport = new ProductExcelExport($this->listHandler, $query);
        return Excel::download($excelExport, 'products.xlsx');
    }
}

<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Controllers\Web;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Products\Application\DTOs\ProductFilterDTO;
use Modules\Products\Infrastructure\Http\Export\ProductExcelExport;
use Modules\Products\Infrastructure\Http\Export\ProductPdfExport;
use Modules\Products\Application\Queries\ListProduct\ListProductHandler;

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

        if ($format === 'pdf') {
            $pdfExport = new ProductPdfExport($this->listHandler, $filters);
            return $pdfExport->export();
        }

        return Excel::download(new ProductExcelExport($filters), 'products-export.xlsx');
    }
}

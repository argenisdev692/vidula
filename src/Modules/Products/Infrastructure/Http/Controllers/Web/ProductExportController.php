<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Controllers\Web;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Products\Application\DTOs\ProductFilterDTO;
use Modules\Products\Infrastructure\Http\Export\ProductExcelExport;
use Modules\Products\Infrastructure\Http\Export\ProductPdfExport;

final class ProductExportController
{
    public function __invoke(Request $request): mixed
    {
        $filters = ProductFilterDTO::from($request->all());
        $format = $request->query('format', 'excel');

        if ($format === 'pdf') {
            return (new ProductPdfExport($filters))->stream();
        }

        return Excel::download(new ProductExcelExport($filters), 'products-export.xlsx');
    }
}

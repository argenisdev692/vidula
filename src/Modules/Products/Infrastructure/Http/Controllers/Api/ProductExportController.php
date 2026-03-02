<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Product\Application\DTOs\ProductFilterDTO;
use Modules\Product\Infrastructure\Http\Export\ProductExcelExport;
use Modules\Product\Infrastructure\Http\Export\ProductPdfExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ProductExportController extends Controller
{
    public function __invoke(Request $request): mixed
    {
        $filters = ProductFilterDTO::from($request->all());
        $format = $request->query('format', 'excel');

        if ($format === 'pdf') {
            $pdfExport = new ProductPdfExport($filters);
            return $pdfExport->stream();
        }

        $excelExport = new ProductExcelExport($filters);
        return Excel::download($excelExport, 'products.xlsx');
    }
}

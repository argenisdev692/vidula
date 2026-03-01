<?php

declare(strict_types=1);

namespace Modules\CompanyData\Infrastructure\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Modules\CompanyData\Application\DTOs\CompanyDataFilterDTO;
use Modules\CompanyData\Infrastructure\Http\Export\CompanyDataExcelExport;
use Modules\CompanyData\Infrastructure\Http\Export\CompanyDataPdfExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class CompanyDataExportController extends Controller
{
    public function __invoke(Request $request): Response|BinaryFileResponse
    {
        $filters = CompanyDataFilterDTO::from($request->all());
        $format = $request->query('format', 'excel');

        return match ($format) {
            'excel' => Excel::download(
                new CompanyDataExcelExport($filters),
                'company-export-' . now()->format('Y-m-d') . '.xlsx'
            ),
            'pdf' => (new CompanyDataPdfExport($filters))->stream(),
            default => response()->json(['error' => 'Invalid format'], 422),
        };
    }
}

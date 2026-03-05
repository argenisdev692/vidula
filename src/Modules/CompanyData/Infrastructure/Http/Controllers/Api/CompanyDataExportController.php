<?php

declare(strict_types=1);

namespace Modules\CompanyData\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Modules\CompanyData\Application\DTOs\CompanyDataFilterDTO;
use Modules\CompanyData\Infrastructure\Http\Export\CompanyDataExcelExport;
use Modules\CompanyData\Infrastructure\Http\Export\CompanyDataPdfExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @OA\Tag(name="CompanyData Export", description="Export company data to Excel or PDF")
 */
final class CompanyDataExportController
{
    /**
     * @OA\Get(
     *     path="/company-data/data/admin/export",
     *     tags={"CompanyData Export"},
     *     summary="Export company data",
     *     @OA\Parameter(name="format", in="query", required=false, @OA\Schema(type="string", enum={"excel", "pdf"}, default="excel")),
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="date_from", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="File download"),
     *     security={{"sanctum": {}}}
     * )
     */
    public function __invoke(Request $request): Response|BinaryFileResponse
    {
        $filters = CompanyDataFilterDTO::from($request->all());
        $format = $request->query('format', 'excel');

        return match ($format) {
            'pdf' => (new CompanyDataPdfExport($filters))->stream(),
            default => Excel::download(
                new CompanyDataExcelExport($filters),
                'company-export-' . now()->format('Y-m-d') . '.xlsx',
            ),
        };
    }
}

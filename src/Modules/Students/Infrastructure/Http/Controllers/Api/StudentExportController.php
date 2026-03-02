<?php

declare(strict_types=1);

namespace Modules\Student\Infrastructure\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Student\Application\DTOs\StudentFilterDTO;
use Modules\Student\Infrastructure\Http\Export\StudentExcelExport;
use Modules\Student\Infrastructure\Http\Export\StudentPdfExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class StudentExportController extends Controller
{
    public function __invoke(Request $request): mixed
    {
        $filters = StudentFilterDTO::from($request->all());
        $format = $request->query('format', 'excel');

        if ($format === 'pdf') {
            $pdfExport = new StudentPdfExport($filters);
            return $pdfExport->stream();
        }

        $excelExport = new StudentExcelExport($filters);
        return Excel::download($excelExport, 'students.xlsx');
    }
}

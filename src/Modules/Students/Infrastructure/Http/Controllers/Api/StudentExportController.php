<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Students\Application\DTOs\StudentFilterDTO;
use Modules\Students\Infrastructure\Http\Export\StudentExcelExport;
use Modules\Students\Infrastructure\Http\Export\StudentPdfExport;

final class StudentExportController
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

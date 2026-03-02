<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\Users\Application\DTOs\UserFilterDTO;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Modules\Users\Infrastructure\Http\Export\UserExcelExport;
use Modules\Users\Infrastructure\Http\Export\UserPdfExport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * UserExportController
 */
final class UserExportController
{
    public function __construct(
        private readonly UserRepositoryPort $repository
    ) {
    }

    public function __invoke(Request $request): mixed
    {
        $format = $request->query('format', 'excel');
        $filters = UserFilterDTO::from($request->all());

        if ($format === 'pdf') {
            $pdfExport = new UserPdfExport($filters, $this->repository);
            return $pdfExport->stream();
        }

        $excelExport = new UserExcelExport($filters);
        return Excel::download($excelExport, 'users.xlsx');
    }
}


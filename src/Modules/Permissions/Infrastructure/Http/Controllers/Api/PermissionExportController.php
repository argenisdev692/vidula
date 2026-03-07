<?php

declare(strict_types=1);

namespace Modules\Permissions\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Permissions\Application\DTOs\PermissionFilterDTO;
use Modules\Permissions\Infrastructure\Http\Export\PermissionExcelExport;
use Modules\Permissions\Infrastructure\Http\Export\PermissionPdfExport;

final class PermissionExportController
{
    public function __invoke(Request $request): mixed
    {
        $format = $request->query('format', 'excel');
        $filters = PermissionFilterDTO::from($request->all());

        if ($format === 'pdf') {
            return (new PermissionPdfExport($filters))->stream();
        }

        return Excel::download(new PermissionExcelExport($filters), 'permissions.xlsx');
    }
}

<?php

declare(strict_types=1);

namespace Modules\Roles\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Roles\Application\DTOs\RoleFilterDTO;
use Modules\Roles\Infrastructure\Http\Export\RoleExcelExport;
use Modules\Roles\Infrastructure\Http\Export\RolePdfExport;

final class RoleExportController
{
    public function __invoke(Request $request): mixed
    {
        $format = $request->query('format', 'excel');
        $filters = RoleFilterDTO::from($request->all());

        if ($format === 'pdf') {
            return (new RolePdfExport($filters))->stream();
        }

        return Excel::download(new RoleExcelExport($filters), 'roles.xlsx');
    }
}

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

    /**
     * @OA\Get(
     *     path="/users/data/admin/export",
     *     tags={"Admin Users"},
     *     summary="Export users to Excel or PDF",
     *     @OA\Parameter(name="format", in="query", required=false, @OA\Schema(type="string", enum={"excel","pdf"})),
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"active","suspended","banned","deleted","pending_setup"})),
     *     @OA\Parameter(name="date_from", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Users exported successfully")
     * )
     */
    public function __invoke(Request $request): mixed
    {
        $format = $request->query('format', 'excel');
        $filters = UserFilterDTO::from($request->all());

        if ($format === 'pdf') {
            $pdfExport = new UserPdfExport($filters);
            return $pdfExport->stream();
        }

        $excelExport = new UserExcelExport($filters);
        return Excel::download($excelExport, 'users.xlsx');
    }
}


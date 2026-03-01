<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * UserExportController
 */
final class UserExportController
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        // Place holder for export logic (Excel/PDF)
        // Usually dispatches a job or uses a Service

        return response()->download(storage_path('app/exports/users.xlsx'))->deleteFileAfterSend();
    }
}

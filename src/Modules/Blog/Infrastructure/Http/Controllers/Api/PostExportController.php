<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\Blog\Application\DTOs\PostFilterDTO;
use Modules\Blog\Infrastructure\Http\Export\PostExcelExport;
use Modules\Blog\Infrastructure\Http\Export\PostPdfExport;
use Maatwebsite\Excel\Facades\Excel;

final class PostExportController
{
    public function __invoke(Request $request): mixed
    {
        $format = $request->query('format', 'excel');
        $filters = PostFilterDTO::from($request->all());

        if ($format === 'pdf') {
            return (new PostPdfExport($filters))->stream();
        }

        return Excel::download(new PostExcelExport($filters), 'posts.xlsx');
    }
}

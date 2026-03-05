<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Blog\Application\DTOs\BlogCategoryFilterDTO;
use Modules\Blog\Application\Queries\ListBlogCategories\ListBlogCategoriesHandler;
use Modules\Blog\Application\Queries\ListBlogCategories\ListBlogCategoriesQuery;
use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;
use Modules\Blog\Infrastructure\Http\Export\BlogCategoryExcelExport;
use Modules\Blog\Infrastructure\Http\Export\BlogCategoryPdfExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * BlogCategoryExportController — Handles blog category data exports.
 *
 * Follows hexagonal architecture by injecting repository and handler.
 */
final class BlogCategoryExportController
{
    public function __construct(
        private readonly BlogCategoryRepositoryPort $repository,
        private readonly ListBlogCategoriesHandler $handler,
    ) {
    }

    public function __invoke(Request $request): Response|BinaryFileResponse
    {
        $filters = BlogCategoryFilterDTO::from($request->all());
        $format = $request->query('format', 'excel');

        return match ($format) {
            'excel' => $this->exportExcel($filters),
            'pdf' => $this->exportPdf($filters),
            default => response()->json(['error' => 'Invalid format. Use "excel" or "pdf"'], 422),
        };
    }

    /**
     * Export to Excel format
     */
    private function exportExcel(BlogCategoryFilterDTO $filters): BinaryFileResponse
    {
        $export = new BlogCategoryExcelExport($filters, $this->repository);

        return Excel::download(
            $export,
            'blog-categories-export-' . now()->format('Y-m-d') . '.xlsx',
        );
    }

    /**
     * Export to PDF format
     */
    private function exportPdf(BlogCategoryFilterDTO $filters): Response
    {
        $query = new ListBlogCategoriesQuery($filters);
        $pdfExport = new BlogCategoryPdfExport($this->handler, $query);

        return $pdfExport->stream();
    }
}

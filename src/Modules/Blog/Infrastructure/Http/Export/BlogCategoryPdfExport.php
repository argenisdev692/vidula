<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Export;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Modules\Blog\Application\Queries\ListBlogCategories\ListBlogCategoriesHandler;
use Modules\Blog\Application\Queries\ListBlogCategories\ListBlogCategoriesQuery;

final class BlogCategoryPdfExport
{
    public function __construct(
        private readonly ListBlogCategoriesHandler $handler,
        private readonly ListBlogCategoriesQuery $query,
    ) {
    }

    public function stream(): Response
    {
        $result = $this->handler->handle($this->query);

        // Transform categories using pipe operator and transformer
        $rows = $result['data']
            |> (fn($categories) => array_map(BlogCategoryExportTransformer::transformForPdf(...), $categories));

        $html = '<html><head><meta charset="UTF-8"><style>'
            . 'body{font-family:sans-serif;font-size:12px;color:#1a1a2e;}'
            . 'h1{font-size:18px;margin-bottom:4px;}'
            . 'p.meta{font-size:10px;color:#6c6c8a;margin-bottom:16px;}'
            . 'table{width:100%;border-collapse:collapse;}'
            . 'th{background:#2456e8;color:#fff;text-align:left;padding:8px 10px;font-size:11px;}'
            . 'td{padding:6px 10px;border-bottom:1px solid #e2e2e2;font-size:11px;}'
            . 'tr:nth-child(even){background:#f5f5ff;}'
            . '</style></head><body>'
            . '<h1>Blog Categories Report</h1>'
            . '<p class="meta">Generated: ' . now()->format('F j, Y H:i') . '</p>'
            . '<table><thead><tr><th>Name</th><th>Description</th><th>Created At</th></tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>'
                . '<td>' . e($row['blog_category_name'] ?? '') . '</td>'
                . '<td>' . e($row['blog_category_description'] ?? '—') . '</td>'
                . '<td>' . ($row['created_at'] ?? '—') . '</td>'
                . '</tr>';
        }

        $html .= '</tbody></table></body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        return $pdf->stream('blog-categories-report-' . now()->format('Y-m-d') . '.pdf');
    }
}

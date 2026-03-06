<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Export;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Modules\Blog\Application\DTOs\PostFilterDTO;
use Modules\Blog\Infrastructure\Persistence\Eloquent\Models\PostEloquentModel;

final class PostPdfExport
{
    public function __construct(
        private readonly PostFilterDTO $filters,
    ) {
    }

    public function stream(): Response
    {
        $rows = $this->query()
            ->get()
            ->map(fn($post) => PostExportTransformer::transformForPdf($post))
            ->toArray();

        $pdf = Pdf::loadView('exports.pdf.posts', [
            'title' => 'Posts Report',
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'rows' => $rows,
        ]);

        return $pdf->stream('posts-report-' . now()->format('Y-m-d') . '.pdf');
    }

    private function query(): Builder
    {
        $query = PostEloquentModel::query()
            ->with(['category:id,blog_category_name'])
            ->select([
                'id',
                'uuid',
                'post_title',
                'post_title_slug',
                'category_id',
                'post_status',
                'published_at',
                'created_at',
                'deleted_at',
            ]);

        return $query
            ->when(
                $this->filters->search,
                fn($q, $search) => $q->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('post_title', 'like', "%{$search}%")
                        ->orWhere('post_excerpt', 'like', "%{$search}%")
                        ->orWhere('post_content', 'like', "%{$search}%");
                }),
            )
            ->when(
                $this->filters->status === 'deleted',
                fn($q) => $q->onlyTrashed(),
                fn($q) => $q->when(
                    $this->filters->status === 'active' || $this->filters->status === null,
                    fn($activeQuery) => $activeQuery->whereNull('deleted_at'),
                )->when(
                    in_array($this->filters->status, ['draft', 'published', 'scheduled', 'archived'], true),
                    fn($statusQuery) => $statusQuery
                        ->whereNull('deleted_at')
                        ->where('post_status', $this->filters->status),
                ),
            )
            ->when(
                $this->filters->dateFrom || $this->filters->dateTo,
                fn($q) => $q->inDateRange($this->filters->dateFrom, $this->filters->dateTo),
            )
            ->orderBy($this->filters->sortBy ?? 'created_at', $this->filters->sortDir ?? 'desc');
    }
}

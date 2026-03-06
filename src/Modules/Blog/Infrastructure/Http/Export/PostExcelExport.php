<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Export;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Modules\Blog\Application\DTOs\PostFilterDTO;
use Modules\Blog\Infrastructure\Persistence\Eloquent\Models\PostEloquentModel;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class PostExcelExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithStyles
{
    use Exportable;

    public function __construct(
        private readonly PostFilterDTO $filters,
    ) {
    }

    public function query(): Builder
    {
        return $this->baseQuery();
    }

    public function headings(): array
    {
        return [
            'UUID',
            'Title',
            'Slug',
            'Category',
            'Status',
            'Published At',
            'Created At',
        ];
    }

    public function map(mixed $post): array
    {
        return PostExportTransformer::transform($post);
    }

    public function title(): string
    {
        return 'Posts Export';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function baseQuery(): Builder
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

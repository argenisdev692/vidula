<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Export;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Modules\Blog\Application\DTOs\BlogCategoryFilterDTO;
use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;

/**
 * BlogCategoryExcelExport — Excel export using repository and transformer.
 *
 * Follows hexagonal architecture by using repository port instead of direct Eloquent access.
 */
final class BlogCategoryExcelExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithTitle,
    WithStyles
{
    use Exportable;

    public function __construct(
        private readonly BlogCategoryFilterDTO $filters,
        private readonly BlogCategoryRepositoryPort $repository,
    ) {
    }

    /**
     * Get collection of blog categories from repository
     */
    public function collection(): Collection
    {
        $result = $this->repository->findAllPaginated(
            filters: $this->filters->toArray(),
            page: 1,
            perPage: 10000, // Large limit for export
        );

        return collect($result['data']);
    }

    /**
     * Define Excel headings
     */
    public function headings(): array
    {
        return [
            'UUID',
            'Name',
            'Description',
            'Image',
            'User ID',
            'Created At',
            'Updated At',
            'Deleted At',
        ];
    }

    /**
     * Map blog category entity to array using transformer with pipe operator
     */
    public function map($category): array
    {
        return BlogCategoryExportTransformer::transformForExcel($category);
    }

    /**
     * Excel sheet title
     */
    public function title(): string
    {
        return 'Blog Categories Export';
    }

    /**
     * Apply styles to worksheet
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

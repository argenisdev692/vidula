<?php

declare(strict_types=1);

namespace Modules\Clients\Infrastructure\Http\Export;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Modules\Clients\Application\DTOs\ClientFilterDTO;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;

/**
 * ClientExcelExport — Excel export using repository and transformer
 * 
 * Follows hexagonal architecture by using repository port instead of direct Eloquent access.
 */
final class ClientExcelExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithTitle,
    WithStyles
{
    use Exportable;

    public function __construct(
        private readonly ClientFilterDTO $filters,
        private readonly ClientRepositoryPort $repository
    ) {
    }

    /**
     * Get collection of clients from repository
     */
    public function collection(): Collection
    {
        $result = $this->repository->findAllPaginated(
            filters: $this->filters->toArray(),
            page: 1,
            perPage: 10000 // Large limit for export
        );

        return collect($result['data']);
    }

    /**
     * Define Excel headings
     */
    public function headings(): array
    {
        return [
            'ID',
            'UUID',
            'Client Name',
            'Email',
            'Phone',
            'Address',
            'Website',
            'Facebook',
            'Instagram',
            'LinkedIn',
            'Twitter',
            'Latitude',
            'Longitude',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * Map client entity to array using transformer with pipe operator
     */
    public function map($client): array
    {
        return ClientExportTransformer::transformForExcel($client);
    }

    /**
     * Excel sheet title
     */
    public function title(): string
    {
        return 'Clients Export';
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


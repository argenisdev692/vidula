<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Http\Export;

use App\Models\Student as StudentEloquentModel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Modules\Students\Application\DTOs\StudentFilterDTO;

final class StudentExcelExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithTitle,
    WithStyles
{
    use Exportable;

    public function __construct(
        private readonly StudentFilterDTO $filters
    ) {
    }

    public function query(): Builder
    {
        /** @var Builder $query */
        $query = StudentEloquentModel::query()
            ->select([
                'id',
                'uuid',
                'company_name',
                'name',
                'email',
                'phone',
                'address',
                'website',
                'created_at',
            ])
            ->whereNull('deleted_at')
            ->when(
                $this->filters->search,
                fn($q, $s) =>
                $q->where('company_name', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%")
            )
            ->when(
                $this->filters->dateFrom || $this->filters->dateTo,
                fn($q) => $q->inDateRange($this->filters->dateFrom, $this->filters->dateTo)
            )
            ->orderBy($this->filters->sortBy ?? 'created_at', $this->filters->sortDir ?? 'desc');

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'UUID',
            'Company Name',
            'Contact Name',
            'Email',
            'Phone',
            'Address',
            'Website',
            'Created At',
        ];
    }

    public function map($company): array
    {
        return [
            $company->id,
            $company->uuid,
            $company->company_name,
            $company->name,
            $company->email,
            $company->phone,
            $company->address,
            $company->website,
            $company->created_at?->toIso8601String(),
        ];
    }

    public function title(): string
    {
        return 'Company Profiles Export';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

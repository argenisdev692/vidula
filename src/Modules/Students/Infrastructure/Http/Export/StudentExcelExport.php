<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Http\Export;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Modules\Students\Application\DTOs\StudentFilterDTO;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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
            ->withTrashed()
            ->select([
                'id',
                'uuid',
                'name',
                'email',
                'phone',
                'dni',
                'birth_date',
                'address',
                'status',
                'active',
                'created_at',
            ])
            ->when(
                $this->filters->search,
                fn(Builder $q, string $s) =>
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('dni', 'like', "%{$s}%")
            )
            ->when(
                $this->filters->status,
                fn(Builder $q, string $status) => $q->where('status', $status)
            )
            ->when(
                $this->filters->dateFrom || $this->filters->dateTo,
                fn(Builder $q) => $q->inDateRange($this->filters->dateFrom, $this->filters->dateTo)
            )
            ->orderBy($this->filters->sortBy ?? 'created_at', $this->filters->sortDir ?? 'desc');

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'UUID',
            'Name',
            'Email',
            'Phone',
            'DNI',
            'Birth Date',
            'Address',
            'Status',
            'Active',
            'Created At',
        ];
    }

    public function map($student): array
    {
        return [
            $student->id,
            $student->uuid,
            $student->name,
            $student->email ?? '—',
            $student->phone ?? '—',
            $student->dni ?? '—',
            $student->birth_date ?? '—',
            $student->address ?? '—',
            $student->status,
            $student->active ? 'Yes' : 'No',
            $student->created_at?->format('F j, Y') ?? '—',
        ];
    }

    public function title(): string
    {
        return 'Students Export';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

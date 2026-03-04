<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Export;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;
use Modules\Users\Application\DTOs\UserFilterDTO;
use Illuminate\Database\Eloquent\Builder;

final class UserExcelExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithTitle,
    WithStyles
{
    use Exportable;

    public function __construct(
        private readonly UserFilterDTO $filters
    ) {
    }

    public function query(): Builder
    {
        /** @var Builder $query */
        $query = UserEloquentModel::query()
            ->select([
                'id',
                'uuid',
                'name',
                'last_name',
                'email',
                'username',
                'phone',
                'city',
                'state',
                'country',
                'created_at'
            ])
            ->whereNull('deleted_at')
            ->when($this->filters->search, fn($q, $s) => $q->where(
                fn($bq) =>
                $bq->where('name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
            ))
            ->when(
                $this->filters->dateFrom || $this->filters->dateTo,
                fn($q) => $q->inDateRange($this->filters->dateFrom, $this->filters->dateTo)
            )
            ->orderBy($this->filters->sortBy, $this->filters->sortDir);

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'UUID',
            'First Name',
            'Last Name',
            'Email',
            'Username',
            'Phone',
            'City',
            'State',
            'Country',
            'Created At',
        ];
    }

    /**
     * @param UserEloquentModel $user
     */
    public function map(mixed $user): array
    {
        return UserExportTransformer::transform($user);
    }

    public function title(): string
    {
        return 'Users Export';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

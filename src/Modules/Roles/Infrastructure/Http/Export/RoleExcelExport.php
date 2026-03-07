<?php

declare(strict_types=1);

namespace Modules\Roles\Infrastructure\Http\Export;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Roles\Application\DTOs\RoleFilterDTO;
use Modules\Roles\Infrastructure\Persistence\Eloquent\Models\RoleEloquentModel;

final class RoleExcelExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    public function __construct(
        private readonly RoleFilterDTO $filters,
    ) {
    }

    public function query(): Builder
    {
        return RoleEloquentModel::query()
            ->select(['id', 'uuid', 'name', 'guard_name', 'created_at', 'updated_at'])
            ->with(['permissions:id,name,guard_name'])
            ->withCount('users')
            ->when($this->filters->search, fn ($builder, $search) => $builder->where('name', 'like', "%{$search}%"))
            ->when($this->filters->guardName, fn ($builder, $guardName) => $builder->where('guard_name', $guardName))
            ->orderBy($this->filters->sortBy ?? 'created_at', $this->filters->sortDir ?? 'desc');
    }

    public function headings(): array
    {
        return ['UUID', 'Name', 'Guard', 'Permissions', 'Users Count', 'Created At'];
    }

    public function map(mixed $row): array
    {
        return array_values(RoleExportTransformer::transform($row));
    }
}

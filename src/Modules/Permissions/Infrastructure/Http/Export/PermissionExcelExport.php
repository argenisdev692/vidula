<?php

declare(strict_types=1);

namespace Modules\Permissions\Infrastructure\Http\Export;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Permissions\Application\DTOs\PermissionFilterDTO;
use Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel;

final class PermissionExcelExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    public function __construct(
        private readonly PermissionFilterDTO $filters,
    ) {
    }

    public function query(): Builder
    {
        return PermissionEloquentModel::query()
            ->select(['id', 'uuid', 'name', 'guard_name', 'created_at', 'updated_at'])
            ->with(['roles:id,uuid,name,guard_name'])
            ->withCount('roles')
            ->when($this->filters->search, fn ($builder, $search) => $builder->where('name', 'like', "%{$search}%"))
            ->when($this->filters->guardName, fn ($builder, $guardName) => $builder->where('guard_name', $guardName))
            ->orderBy($this->filters->sortBy ?? 'created_at', $this->filters->sortDir ?? 'desc');
    }

    public function headings(): array
    {
        return ['UUID', 'Name', 'Guard', 'Roles', 'Roles Count', 'Created At'];
    }

    public function map(mixed $row): array
    {
        return array_values(PermissionExportTransformer::transform($row));
    }
}

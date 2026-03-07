<?php

declare(strict_types=1);

namespace Modules\Permissions\Infrastructure\Http\Export;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Modules\Permissions\Application\DTOs\PermissionFilterDTO;
use Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel;

final class PermissionPdfExport
{
    public function __construct(
        private readonly PermissionFilterDTO $filters,
    ) {
    }

    public function stream(): Response
    {
        $permissions = $this->query()->get();

        $rows = $permissions
            |> (fn ($collection) => $collection->map(fn (PermissionEloquentModel $permission) => PermissionExportTransformer::transform($permission)))
            |> (fn ($collection) => $collection->toArray());

        $pdf = Pdf::loadView('exports.pdf.permissions', [
            'title' => 'Permissions Report',
            'generatedAt' => now()->format('F j, Y H:i'),
            'rows' => $rows,
        ]);

        return $pdf->stream('permissions-report-' . now()->format('Y-m-d') . '.pdf');
    }

    private function query(): Builder
    {
        return PermissionEloquentModel::query()
            ->select(['id', 'uuid', 'name', 'guard_name', 'created_at', 'updated_at'])
            ->with(['roles:id,uuid,name,guard_name'])
            ->withCount('roles')
            ->when($this->filters->search, fn ($builder, $search) => $builder->where('name', 'like', "%{$search}%"))
            ->when($this->filters->guardName, fn ($builder, $guardName) => $builder->where('guard_name', $guardName))
            ->orderBy($this->filters->sortBy ?? 'created_at', $this->filters->sortDir ?? 'desc');
    }
}

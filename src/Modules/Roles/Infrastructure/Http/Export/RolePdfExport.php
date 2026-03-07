<?php

declare(strict_types=1);

namespace Modules\Roles\Infrastructure\Http\Export;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Modules\Roles\Application\DTOs\RoleFilterDTO;
use Modules\Roles\Infrastructure\Persistence\Eloquent\Models\RoleEloquentModel;

final class RolePdfExport
{
    public function __construct(
        private readonly RoleFilterDTO $filters,
    ) {
    }

    public function stream(): Response
    {
        $roles = $this->query()->get();

        $rows = $roles
            |> (fn ($collection) => $collection->map(fn (RoleEloquentModel $role) => RoleExportTransformer::transform($role)))
            |> (fn ($collection) => $collection->toArray());

        $pdf = Pdf::loadView('exports.pdf.roles', [
            'title' => 'Roles Report',
            'generatedAt' => now()->format('F j, Y H:i'),
            'rows' => $rows,
        ]);

        return $pdf->stream('roles-report-' . now()->format('Y-m-d') . '.pdf');
    }

    private function query(): Builder
    {
        return RoleEloquentModel::query()
            ->select(['id', 'uuid', 'name', 'guard_name', 'created_at', 'updated_at'])
            ->with(['permissions:id,name,guard_name'])
            ->withCount('users')
            ->when($this->filters->search, fn ($builder, $search) => $builder->where('name', 'like', "%{$search}%"))
            ->when($this->filters->guardName, fn ($builder, $guardName) => $builder->where('guard_name', $guardName))
            ->orderBy($this->filters->sortBy ?? 'created_at', $this->filters->sortDir ?? 'desc');
    }
}

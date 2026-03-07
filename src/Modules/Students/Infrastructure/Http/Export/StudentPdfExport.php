<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Http\Export;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Modules\Students\Application\Queries\ReadModels\StudentReadModel;
use Modules\Students\Application\DTOs\StudentFilterDTO;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;

final class StudentPdfExport
{
    public function __construct(
        private readonly StudentFilterDTO $filters
    ) {
    }

    public function stream(): Response
    {
        $rows = StudentEloquentModel::query()
            ->withTrashed()
            ->select([
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
                'deleted_at',
            ])
            ->when(
                $this->filters->search,
                fn($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('dni', 'like', "%{$s}%")
            )
            ->when(
                $this->filters->status,
                fn($q, $status) => $q->where('status', $status)
            )
            ->when(
                $this->filters->dateFrom || $this->filters->dateTo,
                fn($q) => $q->inDateRange($this->filters->dateFrom, $this->filters->dateTo)
            )
            ->orderBy($this->filters->sortBy ?? 'created_at', $this->filters->sortDir ?? 'desc')
            ->get()
            ->map(static fn (StudentEloquentModel $student): array => StudentExportTransformer::transformForPdf(new StudentReadModel(
                uuid: $student->uuid,
                name: $student->name,
                email: $student->email,
                phone: $student->phone,
                dni: $student->dni,
                birthDate: $student->birth_date?->format('Y-m-d'),
                address: $student->address,
                avatar: null,
                notes: null,
                status: $student->status,
                active: $student->active,
                createdAt: $student->created_at?->toIso8601String(),
                updatedAt: null,
                deletedAt: $student->deleted_at?->toIso8601String(),
            )))
            ->all();

        $pdf = Pdf::loadView('exports.pdf.students', [
            'title' => 'Students Report',
            'generatedAt' => now()->format('F j, Y H:i'),
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('students-report-' . now()->format('Y-m-d') . '.pdf');
    }
}

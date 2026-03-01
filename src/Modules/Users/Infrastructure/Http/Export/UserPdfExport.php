<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Export;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;
use Modules\Users\Application\DTOs\UserFilterDTO;

final class UserPdfExport
{
    public function __construct(
        private readonly UserFilterDTO $filters
    ) {
    }

    public function stream(): Response
    {
        $rows = UserEloquentModel::query()
            ->select([
                'uuid',
                'name',
                'last_name',
                'email',
                'phone',
                'city',
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
            ->orderBy($this->filters->sortBy, $this->filters->sortDir)
            ->get();

        $pdf = Pdf::loadView('exports.pdf.users', [
            'title' => 'Users Report',
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'rows' => $rows,
        ]);

        return $pdf->stream('users-report-' . now()->format('Y-m-d') . '.pdf');
    }
}

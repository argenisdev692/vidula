<?php

declare(strict_types=1);

namespace Modules\CompanyData\Infrastructure\Http\Export;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Modules\CompanyData\Application\DTOs\CompanyDataFilterDTO;
use Modules\CompanyData\Infrastructure\Persistence\Eloquent\Models\CompanyDataEloquentModel;

final class CompanyDataPdfExport
{
    public function __construct(
        private readonly CompanyDataFilterDTO $filters,
    ) {
    }

    public function stream(): Response
    {
        $rows = CompanyDataEloquentModel::query()
            ->select([
                'company_name',
                'name',
                'email',
                'phone',
                'website',
                'created_at',
            ])
            ->whereNull('deleted_at')
            ->when(
                $this->filters->search,
                fn($q, $s) => $q->where(function ($q) use ($s): void {
                    $q->where('company_name', 'like', "%{$s}%")
                        ->orWhere('name', 'like', "%{$s}%");
                }),
            )
            ->when(
                $this->filters->dateFrom || $this->filters->dateTo,
                fn($q) => $q->inDateRange($this->filters->dateFrom, $this->filters->dateTo),
            )
            ->orderBy($this->filters->sortBy ?? 'created_at', $this->filters->sortDir ?? 'desc')
            ->get();

        $pdf = Pdf::loadView('company-data::exports.pdf', [
            'title' => 'Company Profiles Report',
            'generatedAt' => now()->format('F j, Y H:i'),
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('company-report-' . now()->format('Y-m-d') . '.pdf');
    }
}

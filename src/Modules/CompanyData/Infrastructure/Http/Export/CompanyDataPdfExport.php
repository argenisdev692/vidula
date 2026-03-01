<?php

declare(strict_types=1);

namespace Modules\CompanyData\Infrastructure\Http\Export;

use App\Models\CompanyData as CompanyDataEloquentModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Modules\CompanyData\Application\DTOs\CompanyDataFilterDTO;

final class CompanyDataPdfExport
{
    public function __construct(
        private readonly CompanyDataFilterDTO $filters
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
                fn($q, $s) =>
                $q->where('company_name', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%")
            )
            ->when(
                $this->filters->dateFrom || $this->filters->dateTo,
                fn($q) => $q->inDateRange($this->filters->dateFrom, $this->filters->dateTo)
            )
            ->orderBy($this->filters->sortBy ?? 'created_at', $this->filters->sortDir ?? 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.pdf.company_data', [
            'title' => 'Company Profiles Report',
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'rows' => $rows,
        ]);

        return $pdf->stream('company-report-' . now()->format('Y-m-d') . '.pdf');
    }
}

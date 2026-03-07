<?php
declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Export;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Modules\Products\Application\DTOs\ProductFilterDTO;
use Modules\Products\Application\Queries\ReadModels\ProductReadModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;

final class ProductPdfExport
{
    public function __construct(
        private readonly ProductFilterDTO $filters
    ) {}

    public function stream(): Response
    {
        $rows = $this->query()
            ->get()
            ->map(static fn (ProductEloquentModel $product): array => ProductExportTransformer::transformForPdf(ProductReadModel::from([
                'id' => $product->uuid,
                'userId' => $product->user?->uuid ?? '',
                'type' => $product->type,
                'title' => $product->title,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => (float) $product->price,
                'currency' => $product->currency,
                'status' => $product->status,
                'thumbnail' => $product->thumbnail,
                'level' => $product->level,
                'language' => $product->language,
                'createdAt' => $product->created_at?->toIso8601String(),
                'updatedAt' => $product->updated_at?->toIso8601String(),
                'deletedAt' => $product->deleted_at?->toIso8601String(),
            ])))
            ->all();

        $pdf = Pdf::loadView('exports.pdf.products', [
            'rows' => $rows,
            'generatedAt' => now()->format('F j, Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('products-export-' . now()->format('Y-m-d') . '.pdf');
    }

    private function query(): Builder
    {
        return ProductEloquentModel::query()
            ->with(['user:id,uuid'])
            ->when(
                $this->filters->search,
                fn (Builder $q, string $search): Builder => $q->where(function (Builder $inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                })
            )
            ->when(
                $this->filters->userUuid,
                fn (Builder $q, string $userUuid): Builder => $q->whereHas('user', fn (Builder $u): Builder => $u->where('uuid', $userUuid))
            )
            ->when(
                $this->filters->dateFrom || $this->filters->dateTo,
                fn (Builder $q): Builder => $q->inDateRange($this->filters->dateFrom, $this->filters->dateTo)
            )
            ->orderBy($this->filters->sortBy ?? 'created_at', $this->filters->sortDir ?? 'desc');
    }
}

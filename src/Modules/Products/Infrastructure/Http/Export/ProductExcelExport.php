<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Export;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Products\Application\DTOs\ProductFilterDTO;
use Modules\Products\Application\Queries\ReadModels\ProductReadModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;

final class ProductExcelExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private readonly ProductFilterDTO $filters
    ) {
    }

    public function query(): Builder
    {
        return ProductEloquentModel::query()
            ->with(['user:id,uuid'])
            ->when(
                $this->filters->search,
                fn(Builder $q, string $search): Builder => $q
                    ->where(function (Builder $inner) use ($search): void {
                        $inner->where('title', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    })
            )
            ->when(
                $this->filters->userUuid,
                fn(Builder $q, string $userUuid): Builder =>
                $q->whereHas('user', fn(Builder $u): Builder => $u->where('uuid', $userUuid))
            )
            ->when(
                $this->filters->dateFrom,
                fn(Builder $q, string $from): Builder =>
                $q->whereDate('created_at', '>=', $from)
            )
            ->when(
                $this->filters->dateTo,
                fn(Builder $q, string $to): Builder =>
                $q->whereDate('created_at', '<=', $to)
            )
            ->orderBy($this->filters->sortBy ?? 'created_at', $this->filters->sortDir ?? 'desc');
    }

    public function headings(): array
    {
        return ['Title', 'Type', 'Price', 'Currency', 'Level', 'Status', 'Language', 'Created At', 'Updated At'];
    }

    public function map(mixed $row): array
    {
        $transformed = ProductExportTransformer::transformForExcel(
            ProductReadModel::from([
                'id' => $row->uuid,
                'userId' => $row->user?->uuid ?? '',
                'type' => $row->type,
                'title' => $row->title,
                'slug' => $row->slug,
                'description' => $row->description,
                'price' => (float) $row->price,
                'currency' => $row->currency,
                'status' => $row->status,
                'thumbnail' => $row->thumbnail,
                'level' => $row->level,
                'language' => $row->language,
                'createdAt' => $row->created_at?->toIso8601String(),
                'updatedAt' => $row->updated_at?->toIso8601String(),
                'deletedAt' => $row->deleted_at?->toIso8601String(),
            ])
        );

        return [
            $transformed['title'],
            $transformed['type'],
            $transformed['price'],
            $transformed['currency'],
            $transformed['level'],
            $transformed['status'],
            $transformed['language'],
            $transformed['created_at'],
            $transformed['updated_at'],
        ];
    }
}

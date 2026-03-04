<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Export;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Products\Application\DTOs\ProductFilterDTO;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Products\Infrastructure\Persistence\Mappers\ProductMapper;

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
        $readModel = is_array($row)
            ? $row
            : ProductMapper::toDomain($row);

        $product = is_object($readModel) && method_exists($readModel, 'title')
            ? $readModel
            : ProductMapper::toDomain($row);

        $transformed = ProductExportTransformer::transformForExcel(
            \Modules\Products\Application\Queries\ReadModels\ProductReadModel::from([
                'id' => $row->uuid,
                'user_id' => (string) $row->user_id,
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
                'created_at' => $row->created_at?->toIso8601String(),
                'updated_at' => $row->updated_at?->toIso8601String(),
                'deleted_at' => $row->deleted_at?->toIso8601String(),
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

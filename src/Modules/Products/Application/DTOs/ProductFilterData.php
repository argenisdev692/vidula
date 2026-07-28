<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Shared\Application\DTOs\SoftDeleteFilterData;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * List/export filter for the product catalog. Soft-delete `status` is
 * active|suspended (applied via `onlyTrashed()` at the repository); the domain
 * lifecycle lives on `product_status`, and `type` narrows the catalog kind.
 *
 * Sort/pagination fields follow BACKEND-PHP §5.2; `page`/`per_page` stay on the
 * request (capped in the controller) while sort is applied in the repository.
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class ProductFilterData extends SoftDeleteFilterData
{
    /** @var list<string> */
    public const array SORTABLE = ['created_at', 'title', 'price', 'status', 'type'];

    public function __construct(
        ?string $search = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        public ?string $productStatus = null,
        public ?string $type = null,
        public ?string $clientUuid = null,
        public string $sortField = 'created_at',
        public int $sortOrder = -1,
    ) {
        parent::__construct($search, $status, $dateFrom, $dateTo);
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            ...self::baseRules(),
            'status' => ['nullable', 'string', 'in:active,suspended'],
            'product_status' => ['nullable', 'string', 'in:draft,published,archived'],
            'type' => ['nullable', 'string', 'in:classroom,video_tutorial,video_pill'],
            'client_uuid' => ['nullable', 'uuid'],
            'sort_field' => ['nullable', 'string', 'in:created_at,title,price,status,type'],
            'sort_order' => ['nullable', 'integer', 'in:1,-1'],
        ];
    }

    public function resolvedSortField(): string
    {
        return in_array($this->sortField, self::SORTABLE, true)
            ? $this->sortField
            : 'created_at';
    }

    public function resolvedSortDirection(): string
    {
        return $this->sortOrder === 1 ? 'asc' : 'desc';
    }
}

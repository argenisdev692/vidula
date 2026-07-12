<?php

declare(strict_types=1);

namespace Modules\Post\Application\DTOs;

use Illuminate\Validation\Rule;
use Shared\Application\DTOs\SoftDeleteFilterData;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Shared list filter — consumed by ListPostsHandler via the single
 * `PostEloquentModel::scopeApplyFilters()` (BACKEND-PHP §4.1). `status` folds
 * the content lifecycle AND the soft-delete state into one axis, matching the
 * single status <select> on the frontend: draft | published | scheduled |
 * suspended (soft-deleted) — mirrors the Users module's combined-status
 * precedent rather than exposing two independent filters.
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class PostFilterData extends SoftDeleteFilterData
{
    public function __construct(
        ?string $search = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        public ?string $categoryUuid = null,
    ) {
        parent::__construct($search, $status, $dateFrom, $dateTo);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            ...self::baseRules(),
            'status' => ['nullable', 'string', Rule::in(['draft', 'published', 'scheduled', 'suspended'])],
            'category_uuid' => ['nullable', 'uuid'],
        ];
    }
}

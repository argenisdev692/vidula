<?php

declare(strict_types=1);

namespace Modules\Blog\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Shared list filter — consumed by ListBlogCategoriesHandler via the single
 * `BlogCategoryEloquentModel::scopeApplyFilters()` (BACKEND-PHP §4.1 — no
 * duplicated `when()` chains).
 *
 * `status`: active | suspended (soft-deleted). The date range filters on
 * `created_at` with inclusive day boundaries.
 *
 * Serializes to the Inertia `filters` prop, so the output is snake_cased
 * (`date_from` / `date_to`) to honour the frontend snake_case contract.
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class BlogCategoryFilterData extends Data
{
    public function __construct(
        public ?string $search = null,
        public ?string $status = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,suspended'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}

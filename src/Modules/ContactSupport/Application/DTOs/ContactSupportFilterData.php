<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Application\DTOs;

use Shared\Application\DTOs\SoftDeleteFilterData;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Shared list filter — consumed by ListContactSupportsHandler via the single
 * `ContactSupportEloquentModel::scopeApplyFilters()` (BACKEND-PHP §4.1 — no
 * duplicated `when()` chains). Inherits search/status/date from
 * {@see SoftDeleteFilterData} and adds the inbox `read` toggle.
 *
 * - `status`: active | suspended (soft-deleted).
 * - `read`:   read | unread (the `readed` column).
 * - `spam`:   spam | ham (the `is_spam` column — flagged vs legitimate).
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class ContactSupportFilterData extends SoftDeleteFilterData
{
    public function __construct(
        ?string $search = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        public ?string $read = null,
        public ?string $spam = null,
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
            'status' => ['nullable', 'string', 'in:active,suspended'],
            'read' => ['nullable', 'string', 'in:read,unread'],
            'spam' => ['nullable', 'string', 'in:spam,ham'],
        ];
    }
}

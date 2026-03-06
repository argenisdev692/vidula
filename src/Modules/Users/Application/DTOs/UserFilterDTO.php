<?php

declare(strict_types=1);

namespace Modules\Users\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Modules\Users\Domain\Enums\UserStatus;

/**
 * @OA\Schema(
 *     schema="UserFilterDTO",
 *     @OA\Property(property="search", type="string", nullable=true),
 *     @OA\Property(property="status", type="string", enum={"active","suspended","banned","deleted","pending_setup"}, nullable=true),
 *     @OA\Property(property="role", type="string", nullable=true),
 *     @OA\Property(property="date_from", type="string", format="date", nullable=true),
 *     @OA\Property(property="date_to", type="string", format="date", nullable=true),
 *     @OA\Property(property="sort_by", type="string", nullable=true),
 *     @OA\Property(property="sort_dir", type="string", enum={"asc","desc"}, nullable=true),
 *     @OA\Property(property="page", type="integer", minimum=1),
 *     @OA\Property(property="per_page", type="integer", minimum=1, maximum=100)
 * )
 */
#[MapInputName(SnakeCaseMapper::class)]
final class UserFilterDTO extends Data
{
    public function __construct(
        public ?string $search = null,
        public ?UserStatus $status = null,
        public ?string $role = null,
        #[MapInputName('date_from')]
        public ?string $dateFrom = null,
        #[MapInputName('date_to')]
        public ?string $dateTo = null,
        #[MapInputName('sort_by')]
        public ?string $sortBy = 'created_at',
        public ?string $sortDir = 'desc',
        public int $page = 1,
        public int $perPage = 15,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Modules\Roles\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * @OA\Schema(
 *     schema="RoleFilterDTO",
 *     @OA\Property(property="search", type="string", nullable=true),
 *     @OA\Property(property="guard_name", type="string", nullable=true),
 *     @OA\Property(property="sort_by", type="string", nullable=true),
 *     @OA\Property(property="sort_dir", type="string", enum={"asc","desc"}, nullable=true),
 *     @OA\Property(property="page", type="integer", minimum=1),
 *     @OA\Property(property="per_page", type="integer", minimum=1, maximum=100)
 * )
 */
#[MapInputName(SnakeCaseMapper::class)]
final class RoleFilterDTO extends Data
{
    public function __construct(
        public ?string $search = null,
        public ?string $guardName = 'web',
        #[MapInputName('sort_by')]
        public ?string $sortBy = 'created_at',
        public ?string $sortDir = 'desc',
        public int $page = 1,
        public int $perPage = 15,
    ) {
    }
}

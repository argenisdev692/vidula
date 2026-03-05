<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * @OA\Schema(
 *     schema="CompanyDataFilterDTO",
 *     type="object",
 *     @OA\Property(property="search", type="string", nullable=true),
 *     @OA\Property(property="user_uuid", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="date_from", type="string", format="date", nullable=true),
 *     @OA\Property(property="date_to", type="string", format="date", nullable=true),
 *     @OA\Property(property="sort_by", type="string", default="created_at"),
 *     @OA\Property(property="sort_dir", type="string", enum={"asc", "desc"}, default="desc"),
 *     @OA\Property(property="page", type="integer", default=1),
 *     @OA\Property(property="per_page", type="integer", default=15),
 * )
 */
#[MapInputName(SnakeCaseMapper::class)]
final class CompanyDataFilterDTO extends Data
{
    public function __construct(
        public ?string $search = null,
        public ?string $userUuid = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $sortBy = 'created_at',
        public ?string $sortDir = 'desc',
        public int $page = 1,
        public int $perPage = 15,
    ) {
    }
}

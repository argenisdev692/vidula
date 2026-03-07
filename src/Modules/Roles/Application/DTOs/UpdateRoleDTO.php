<?php

declare(strict_types=1);

namespace Modules\Roles\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * @OA\Schema(
 *     schema="UpdateRoleDTO",
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="guard_name", type="string", maxLength=255),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
 * )
 */
#[MapInputName(SnakeCaseMapper::class)]
final class UpdateRoleDTO extends Data
{
    /**
     * @param list<string>|null $permissions
     */
    public function __construct(
        public ?string $name = null,
        public ?string $guardName = null,
        public ?array $permissions = null,
    ) {
    }
}

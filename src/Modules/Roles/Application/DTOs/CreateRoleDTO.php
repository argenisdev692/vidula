<?php

declare(strict_types=1);

namespace Modules\Roles\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * @OA\Schema(
 *     schema="CreateRoleDTO",
 *     required={"name"},
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="guard_name", type="string", maxLength=255),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
 * )
 */
#[MapInputName(SnakeCaseMapper::class)]
final class CreateRoleDTO extends Data
{
    /**
     * @param list<string> $permissions
     */
    public function __construct(
        public string $name,
        public string $guardName = 'web',
        public array $permissions = [],
    ) {
    }
}

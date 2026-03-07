<?php

declare(strict_types=1);

namespace Modules\Roles\Application\Queries\ReadModels;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
final class RoleReadModel extends Data
{
    /**
     * @param list<string> $permissions
     * @param list<PermissionOptionReadModel> $availablePermissions
     */
    public function __construct(
        public string $uuid,
        public string $name,
        public string $guardName,
        public array $permissions,
        public int $usersCount,
        public ?string $createdAt,
        public ?string $updatedAt,
        public array $availablePermissions = [],
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Modules\Roles\Domain\Entities;

use Modules\Roles\Domain\ValueObjects\RoleId;
use Shared\Domain\Entities\AggregateRoot;

final class Role extends AggregateRoot
{
    /**
     * @param list<string> $permissions
     */
    public function __construct(
        public RoleId $id,
        public string $uuid,
        public string $name,
        public string $guardName = 'web',
        public array $permissions = [],
        public int $usersCount = 0,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }
}

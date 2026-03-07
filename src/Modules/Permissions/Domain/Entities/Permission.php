<?php

declare(strict_types=1);

namespace Modules\Permissions\Domain\Entities;

use Modules\Permissions\Domain\ValueObjects\PermissionId;
use Shared\Domain\Entities\AggregateRoot;

final class Permission extends AggregateRoot
{
    public function __construct(
        public PermissionId $id,
        public string $uuid,
        public string $name,
        public string $guardName = 'web',
        public array $roles = [],
        public int $rolesCount = 0,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }
}

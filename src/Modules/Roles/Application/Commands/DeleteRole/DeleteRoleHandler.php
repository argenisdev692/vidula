<?php

declare(strict_types=1);

namespace Modules\Roles\Application\Commands\DeleteRole;

use Modules\Roles\Domain\Exceptions\RoleNotFoundException;
use Modules\Roles\Domain\Ports\RoleRepositoryPort;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class DeleteRoleHandler
{
    public function __construct(
        private RoleRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(DeleteRoleCommand $command): void
    {
        $existing = $this->repository->findByUuid($command->uuid);

        if ($existing === null) {
            throw RoleNotFoundException::forUuid($command->uuid);
        }

        $this->repository->delete($command->uuid);

        $this->audit->log(
            logName: 'roles.deleted',
            description: "Role deleted: {$existing->name}",
            properties: [
                'uuid' => $existing->uuid,
                'name' => $existing->name,
            ],
        );
    }
}

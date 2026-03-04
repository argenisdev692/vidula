<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Commands\RestoreClient;

use Modules\Clients\Domain\Exceptions\ClientNotFoundException;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Clients\Domain\ValueObjects\ClientId;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class RestoreClientHandler
{
    public function __construct(
        private ClientRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(RestoreClientCommand $command): void
    {
        $id = new ClientId($command->id);
        $this->repository->restore($id);

        $this->audit->log(
            logName: 'clients.client',
            description: "Client restored",
            properties: ['uuid' => $command->id],
        );
    }
}

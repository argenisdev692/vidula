<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Commands\DeleteClient;

use Modules\Clients\Domain\Exceptions\ClientNotFoundException;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Clients\Domain\ValueObjects\ClientId;
use Illuminate\Support\Facades\Cache;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class DeleteClientHandler
{
    public function __construct(
        private ClientRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(DeleteClientCommand $command): void
    {
        $id = new ClientId($command->id);
        $client = $this->repository->findById($id);

        if (null === $client) {
            throw ClientNotFoundException::forId($command->id);
        }

        $this->repository->delete($id);

        $this->audit->log(
            logName: 'clients.client',
            description: "Client deleted: {$client->clientName}",
            properties: ['uuid' => $command->id, 'clientName' => $client->clientName],
        );

        try {
            Cache::tags(['clients_list'])->flush();
            Cache::forget("client_{$command->id}");
            Cache::forget("client_{$client->userId->value}");
        } catch (\Exception $e) {
        }
    }
}

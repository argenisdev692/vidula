<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Commands\DeleteClient;

use Modules\Clients\Domain\Exceptions\ClientNotFoundException;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Clients\Domain\ValueObjects\ClientId;
use Illuminate\Support\Facades\Cache;

final readonly class DeleteClientHandler
{
    public function __construct(
        private ClientRepositoryPort $repository
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

        try {
            Cache::tags(['clients_list'])->flush();
            Cache::forget("client_{$command->id}");
            Cache::forget("client_{$client->userId->value}");
        } catch (\Exception $e) {
        }
    }
}

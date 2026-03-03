<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Commands\CreateClient;

use Illuminate\Support\Str;
use Modules\Clients\Domain\Entities\Client;
use Modules\Clients\Domain\Enums\CompanyStatus;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Clients\Domain\ValueObjects\ClientId;
use Modules\Clients\Domain\ValueObjects\UserId;
use Illuminate\Support\Facades\Cache;

final readonly class CreateClientHandler
{
    public function __construct(
        private ClientRepositoryPort $repository
    ) {
    }

    public function handle(CreateClientCommand $command): string
    {
        $dto = $command->dto;
        $uuid = Str::uuid()->toString();

        $client = Client::create(
            id: new ClientId($uuid),
            userId: new UserId($dto->userUuid),
            companyName: $dto->companyName,
            email: $dto->email,
            phone: $dto->phone,
            address: $dto->address
        );

        $this->repository->save($client);

        try {
            Cache::tags(['clients_list'])->flush();
        } catch (\Exception $e) {
        }

        return $uuid;
    }
}

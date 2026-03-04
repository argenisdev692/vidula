<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Commands\CreateClient;

use Illuminate\Support\Str;
use Modules\Clients\Domain\Entities\Client;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Clients\Domain\ValueObjects\ClientId;
use Modules\Clients\Domain\ValueObjects\UserId;
use Modules\Clients\Domain\ValueObjects\SocialLinks;
use Modules\Clients\Domain\ValueObjects\Coordinates;
use Illuminate\Support\Facades\Cache;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class CreateClientHandler
{
    public function __construct(
        private ClientRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    #[\NoDiscard('Client UUID must be captured')]
    public function handle(CreateClientCommand $command): string
    {
        $dto = $command->dto;
        $uuid = Str::uuid()->toString();

        $socialLinks = new SocialLinks(
            facebook: $dto->facebookLink,
            instagram: $dto->instagramLink,
            linkedin: $dto->linkedinLink,
            twitter: $dto->twitterLink,
            website: $dto->website,
        );

        $coordinates = new Coordinates(
            latitude: $dto->latitude,
            longitude: $dto->longitude,
        );

        $client = Client::create(
            id: new ClientId($uuid),
            userId: new UserId($dto->userUuid),
            clientName: $dto->clientName,
            email: $dto->email,
            phone: $dto->phone,
            address: $dto->address,
            nif: $dto->nif,
            socialLinks: $socialLinks,
            coordinates: $coordinates,
        );

        $this->repository->save($client);

        $this->audit->log(
            logName: 'clients.client',
            description: "Client created: {$dto->clientName}",
            properties: ['uuid' => $uuid, 'clientName' => $dto->clientName],
        );

        try {
            Cache::tags(['clients_list'])->flush();
        } catch (\Exception $e) {
        }

        return $uuid;
    }
}

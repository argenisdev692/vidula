<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Commands\UpdateClient;

use Modules\Clients\Domain\Exceptions\ClientNotFoundException;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Clients\Domain\ValueObjects\Coordinates;
use Modules\Clients\Domain\ValueObjects\SocialLinks;
use Modules\Clients\Domain\ValueObjects\ClientId;
use Illuminate\Support\Facades\Cache;

final readonly class UpdateClientHandler
{
    public function __construct(
        private ClientRepositoryPort $repository
    ) {
    }

    public function handle(UpdateClientCommand $command): void
    {
        $id = new ClientId($command->id);
        $client = $this->repository->findById($id);

        if (null === $client) {
            throw ClientNotFoundException::forId($command->id);
        }

        $dto = $command->dto;

        // Create new SocialLinks and Coordinates value objects
        $socialLinks = new SocialLinks(
            facebook: $dto->facebookLink ?? $client->socialLinks?->facebook,
            instagram: $dto->instagramLink ?? $client->socialLinks?->instagram,
            linkedin: $dto->linkedinLink ?? $client->socialLinks?->linkedin,
            twitter: $dto->twitterLink ?? $client->socialLinks?->twitter,
            website: $dto->website ?? $client->socialLinks?->website
        );

        $coordinates = new Coordinates(
            latitude: $dto->latitude ?? $client->coordinates?->latitude,
            longitude: $dto->longitude ?? $client->coordinates?->longitude
        );

        // Use clone with to create updated client
        $updatedClient = clone($client, [
            'companyName' => $dto->companyName ?? $client->companyName,
            'email' => $dto->email ?? $client->email,
            'phone' => $dto->phone ?? $client->phone,
            'address' => $dto->address ?? $client->address,
            'socialLinks' => $socialLinks,
            'coordinates' => $coordinates,
            'updatedAt' => date('c'),
        ]);

        $this->repository->save($updatedClient);

        // Invalidate caches
        try {
            Cache::tags(['clients_list'])->flush();
            Cache::forget("client_read_{$updatedClient->id->value}");
            Cache::forget("client_user_{$updatedClient->userId->value}");
        } catch (\Exception $e) {
            // Tags not supported
        }
    }
}

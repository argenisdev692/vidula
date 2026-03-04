<?php

declare(strict_types=1);

namespace Modules\Clients\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Modules\Clients\Domain\Entities\Client;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class ClientDTO extends Data
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $clientName,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        public ?string $nif,
        public ?string $website,
        public ?string $facebookLink,
        public ?string $instagramLink,
        public ?string $linkedinLink,
        public ?string $twitterLink,
        public ?float $latitude,
        public ?float $longitude,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {
    }

    public static function fromEntity(Client $entity): self
    {
        return new self(
            id: $entity->id->value,
            userId: $entity->userId->value,
            clientName: $entity->clientName,
            email: $entity->email,
            phone: $entity->phone,
            address: $entity->address,
            nif: $entity->nif,
            website: $entity->socialLinks?->website,
            facebookLink: $entity->socialLinks?->facebook,
            instagramLink: $entity->socialLinks?->instagram,
            linkedinLink: $entity->socialLinks?->linkedin,
            twitterLink: $entity->socialLinks?->twitter,
            latitude: $entity->coordinates?->latitude,
            longitude: $entity->coordinates?->longitude,
            createdAt: $entity->createdAt,
            updatedAt: $entity->updatedAt,
        );
    }
}

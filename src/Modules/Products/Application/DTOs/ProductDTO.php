<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Modules\Product\Domain\Entities\Product;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class ProductDTO extends Data
{
    public function __construct(
        public string $id,
        public int $userId,
        public ?string $name,
        public string $companyName,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        public ?string $website,
        public ?string $facebookLink,
        public ?string $instagramLink,
        public ?string $linkedinLink,
        public ?string $twitterLink,
        public ?float $latitude,
        public ?float $longitude,
        public ?string $signaturePath,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {
    }


    public static function fromEntity(Product $entity): self
    {
        return new self(
            id: $entity->id->value,
            userId: $entity->userId->value,
            name: $entity->name,
            companyName: $entity->companyName,
            email: $entity->email,
            phone: $entity->phone,
            address: $entity->address,
            website: $entity->website,
            facebookLink: $entity->facebookLink,
            instagramLink: $entity->instagramLink,
            linkedinLink: $entity->linkedinLink,
            twitterLink: $entity->twitterLink,
            latitude: $entity->latitude,
            longitude: $entity->longitude,
            signaturePath: $entity->signaturePath,
            createdAt: $entity->createdAt,
            updatedAt: $entity->updatedAt,
        );
    }
}

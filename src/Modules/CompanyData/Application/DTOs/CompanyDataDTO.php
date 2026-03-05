<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Modules\CompanyData\Domain\Entities\CompanyData;

/**
 * @OA\Schema(
 *     schema="CompanyDataDTO",
 *     type="object",
 *     required={"id", "userId", "companyName"},
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="user_id", type="string", format="uuid"),
 *     @OA\Property(property="company_name", type="string"),
 *     @OA\Property(property="name", type="string", nullable=true),
 *     @OA\Property(property="email", type="string", format="email", nullable=true),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="website", type="string", format="uri", nullable=true),
 *     @OA\Property(property="facebook_link", type="string", format="uri", nullable=true),
 *     @OA\Property(property="instagram_link", type="string", format="uri", nullable=true),
 *     @OA\Property(property="linkedin_link", type="string", format="uri", nullable=true),
 *     @OA\Property(property="twitter_link", type="string", format="uri", nullable=true),
 *     @OA\Property(property="latitude", type="number", format="float", nullable=true),
 *     @OA\Property(property="longitude", type="number", format="float", nullable=true),
 *     @OA\Property(property="signature_path", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true),
 * )
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class CompanyDataDTO extends Data
{
    public function __construct(
        public string $id,
        public string $userId,
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

    #[\NoDiscard('DTO result must be captured')]
    public static function fromEntity(CompanyData $entity): self
    {
        $socialLinks = $entity->socialLinks->toArray();
        $coordinates = $entity->coordinates->toArray();

        return new self(
            id: $entity->id->value,
            userId: $entity->userId->value,
            name: $entity->name,
            companyName: $entity->companyName,
            email: $entity->email,
            phone: $entity->phone,
            address: $entity->address,
            website: $socialLinks['website'],
            facebookLink: $socialLinks['facebook'],
            instagramLink: $socialLinks['instagram'],
            linkedinLink: $socialLinks['linkedin'],
            twitterLink: $socialLinks['twitter'],
            latitude: $coordinates['latitude'],
            longitude: $coordinates['longitude'],
            signaturePath: $entity->signaturePath,
            createdAt: $entity->createdAt,
            updatedAt: $entity->updatedAt,
        );
    }
}

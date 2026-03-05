<?php

declare(strict_types=1);

namespace Modules\CompanyData\Domain\Entities;

use Modules\CompanyData\Domain\Enums\CompanyStatus;
use Modules\CompanyData\Domain\ValueObjects\CompanyDataId;
use Modules\CompanyData\Domain\ValueObjects\Coordinates;
use Modules\CompanyData\Domain\ValueObjects\SocialLinks;
use Modules\CompanyData\Domain\ValueObjects\UserId;
use Shared\Domain\Entities\AggregateRoot;

/**
 * CompanyData Aggregate Root
 */
final class CompanyData extends AggregateRoot
{
    public function __construct(
        public CompanyDataId $id,
        public UserId $userId,
        public string $companyName,
        public ?string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        public SocialLinks $socialLinks,
        public Coordinates $coordinates,
        public ?string $signaturePath,
        public CompanyStatus $status = CompanyStatus::Active,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $deletedAt = null,
    ) {
    }

    #[\NoDiscard('Created entity must be captured')]
    public static function create(
        CompanyDataId $id,
        UserId $userId,
        string $companyName,
        ?string $email = null,
        ?string $phone = null,
        ?string $address = null,
        SocialLinks $socialLinks = new SocialLinks(),
        Coordinates $coordinates = new Coordinates(null, null),
        CompanyStatus $status = CompanyStatus::Active,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            companyName: $companyName,
            name: null,
            email: $email,
            phone: $phone,
            address: $address,
            socialLinks: $socialLinks,
            coordinates: $coordinates,
            signaturePath: null,
            status: $status,
        );
    }

    #[\NoDiscard('Updated entity must be persisted')]
    public function update(
        string $companyName,
        ?string $email,
        ?string $phone,
        ?string $address,
        SocialLinks $socialLinks,
        Coordinates $coordinates,
    ): self {
        return clone($this, [
            'companyName' => $companyName,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'socialLinks' => $socialLinks,
            'coordinates' => $coordinates,
            'updatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);
    }

    #[\NoDiscard('Soft-deleted entity must be persisted')]
    public function softDelete(): self
    {
        return clone($this, [
            'deletedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);
    }
}

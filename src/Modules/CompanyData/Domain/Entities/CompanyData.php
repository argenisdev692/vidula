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

    public static function create(
        CompanyDataId $id,
        UserId $userId,
        string $companyName,
        ?string $email = null,
        ?string $phone = null,
        ?string $address = null,
        SocialLinks $socialLinks = new SocialLinks(),
        Coordinates $coordinates = new Coordinates(null, null),
        CompanyStatus $status = CompanyStatus::Active
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
            status: $status
        );
    }

    public function update(
        string $companyName,
        ?string $email,
        ?string $phone,
        ?string $address,
        SocialLinks $socialLinks,
        Coordinates $coordinates
    ): self {
        return new self(
            id: $this->id,
            userId: $this->userId,
            companyName: $companyName,
            name: $this->name,
            email: $email,
            phone: $phone,
            address: $address,
            socialLinks: $socialLinks,
            coordinates: $coordinates,
            signaturePath: $this->signaturePath,
            status: $this->status,
            createdAt: $this->createdAt,
            updatedAt: (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            deletedAt: $this->deletedAt
        );
    }

    public function softDelete(): self
    {
        return new self(
            id: $this->id,
            userId: $this->userId,
            companyName: $this->companyName,
            name: $this->name,
            email: $this->email,
            phone: $this->phone,
            address: $this->address,
            socialLinks: $this->socialLinks,
            coordinates: $this->coordinates,
            signaturePath: $this->signaturePath,
            status: $this->status,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            deletedAt: (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
        );
    }
}

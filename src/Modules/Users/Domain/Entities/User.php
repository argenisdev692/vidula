<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Entities;

use Modules\Users\Domain\Enums\UserStatus;
use Modules\Users\Domain\ValueObjects\UserId;
use Shared\Domain\Entities\AggregateRoot;

/**
 * User — Domain Entity (Aggregate Root)
 *
 * Represents the admin-managed User in the Users bounded context.
 * Agnostic of Eloquent / infrastructure.
 */
final class User extends AggregateRoot
{
    public function __construct(
        public UserId $id,
        public string $uuid,
        public string $name,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $username = null,
        public ?string $phone = null,
        public ?string $profilePhotoPath = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $country = null,
        public ?string $zipCode = null,
        public UserStatus $status = UserStatus::Active,
        public ?string $setupToken = null,
        public ?string $setupTokenExpiresAt = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $deletedAt = null,
    ) {
    }

    /**
     * @return string
     */
    #[\NoDiscard]
    public function fullName(): string
    {
        return trim("{$this->name} {$this->lastName}");
    }

    /**
     * Soft delete the user
     */
    public function softDelete(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            name: $this->name,
            lastName: $this->lastName,
            email: $this->email,
            username: $this->username,
            phone: $this->phone,
            profilePhotoPath: $this->profilePhotoPath,
            address: $this->address,
            city: $this->city,
            state: $this->state,
            country: $this->country,
            zipCode: $this->zipCode,
            status: UserStatus::Deleted,
            setupToken: $this->setupToken,
            setupTokenExpiresAt: $this->setupTokenExpiresAt,
            createdAt: $this->createdAt,
            updatedAt: date('Y-m-d H:i:s'),
            deletedAt: date('Y-m-d H:i:s'),
        );
    }

    /**
     * Suspend the user
     */
    public function suspend(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            name: $this->name,
            lastName: $this->lastName,
            email: $this->email,
            username: $this->username,
            phone: $this->phone,
            profilePhotoPath: $this->profilePhotoPath,
            address: $this->address,
            city: $this->city,
            state: $this->state,
            country: $this->country,
            zipCode: $this->zipCode,
            status: UserStatus::Suspended,
            setupToken: $this->setupToken,
            setupTokenExpiresAt: $this->setupTokenExpiresAt,
            createdAt: $this->createdAt,
            updatedAt: date('Y-m-d H:i:s'),
            deletedAt: $this->deletedAt,
        );
    }

    /**
     * Activate the user
     */
    public function activate(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            name: $this->name,
            lastName: $this->lastName,
            email: $this->email,
            username: $this->username,
            phone: $this->phone,
            profilePhotoPath: $this->profilePhotoPath,
            address: $this->address,
            city: $this->city,
            state: $this->state,
            country: $this->country,
            zipCode: $this->zipCode,
            status: UserStatus::Active,
            setupToken: $this->setupToken,
            setupTokenExpiresAt: $this->setupTokenExpiresAt,
            createdAt: $this->createdAt,
            updatedAt: date('Y-m-d H:i:s'),
            deletedAt: null,
        );
    }

    /**
     * Ban the user
     */
    public function ban(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            name: $this->name,
            lastName: $this->lastName,
            email: $this->email,
            username: $this->username,
            phone: $this->phone,
            profilePhotoPath: $this->profilePhotoPath,
            address: $this->address,
            city: $this->city,
            state: $this->state,
            country: $this->country,
            zipCode: $this->zipCode,
            status: UserStatus::Banned,
            setupToken: $this->setupToken,
            setupTokenExpiresAt: $this->setupTokenExpiresAt,
            createdAt: $this->createdAt,
            updatedAt: date('Y-m-d H:i:s'),
            deletedAt: $this->deletedAt,
        );
    }
}

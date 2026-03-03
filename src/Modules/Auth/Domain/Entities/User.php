<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Entities;

use Modules\Auth\Domain\ValueObjects\UserEmail;
use Shared\Domain\Entities\AggregateRoot;
use Modules\Auth\Domain\Events\UserLoggedIn;
use Modules\Auth\Domain\Events\UserCreated;
use Modules\Auth\Domain\Events\UserUpdated;
use Modules\Auth\Domain\Events\UserEmailChanged;

/**
 * User — Domain Entity (Aggregate Root) with PHP 8.5 clone with pattern.
 * 
 * Agnostic of Eloquent / infrastructure.
 * 
 * Features:
 * - Immutable updates using clone with
 * - Domain events for all state changes
 * - Business logic encapsulation
 */
final class User extends AggregateRoot
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $username = null,
        public ?string $profilePhotoPath = null,
        public ?string $phone = null,
        public bool $isEmailVerified = false,
        public string $createdAt = '',
        public string $updatedAt = '',
        public ?string $deletedAt = null,
    ) {
    }

    /**
     * Factory method to create a new user.
     */
    public static function create(
        string $uuid,
        string $name,
        ?string $email = null,
        ?string $username = null,
        ?string $lastName = null,
        ?string $phone = null,
    ): self {
        $user = new self(
            id: 0, // Will be set by repository
            uuid: $uuid,
            name: $name,
            lastName: $lastName,
            email: $email,
            username: $username,
            phone: $phone,
            isEmailVerified: false,
            createdAt: date('c'),
            updatedAt: date('c'),
        );

        $user->recordDomainEvent(new UserCreated(
            uuid: $uuid,
            name: $name,
            email: $email,
            occurredAt: date('c'),
        ));

        return $user;
    }

    /**
     * Update user profile information.
     */
    public function updateProfile(
        string $name,
        ?string $lastName = null,
        ?string $phone = null,
        ?string $username = null,
    ): self {
        $updated = clone $this;
        $updated->name = $name;
        $updated->lastName = $lastName;
        $updated->phone = $phone;
        $updated->username = $username;
        $updated->updatedAt = date('c');

        $updated->recordDomainEvent(new UserUpdated(
            userId: $this->id,
            uuid: $this->uuid,
            changes: ['name', 'lastName', 'phone', 'username'],
            occurredAt: date('c'),
        ));

        return $updated;
    }

    /**
     * Change user email address.
     */
    public function changeEmail(string $email): self
    {
        $updated = clone $this;
        $updated->email = $email;
        $updated->isEmailVerified = false;
        $updated->updatedAt = date('c');

        $updated->recordDomainEvent(new UserEmailChanged(
            userId: $this->id,
            uuid: $this->uuid,
            oldEmail: $this->email,
            newEmail: $email,
            occurredAt: date('c'),
        ));

        return $updated;
    }

    /**
     * Verify user email address.
     */
    public function verifyEmail(): self
    {
        $updated = clone $this;
        $updated->isEmailVerified = true;
        $updated->updatedAt = date('c');
        return $updated;
    }

    /**
     * Update user avatar/profile photo.
     */
    public function updateAvatar(string $path): self
    {
        $updated = clone $this;
        $updated->profilePhotoPath = $path;
        $updated->updatedAt = date('c');
        return $updated;
    }

    /**
     * Remove user avatar/profile photo.
     */
    public function removeAvatar(): self
    {
        $updated = clone $this;
        $updated->profilePhotoPath = null;
        $updated->updatedAt = date('c');
        return $updated;
    }

    /**
     * @return UserEmail|null
     */
    public function getEmailValueObject(): ?UserEmail
    {
        return $this->email ? new UserEmail($this->email) : null;
    }

    /**
     * Records a login event for the user.
     */
    public function logIn(string $provider, string $ipAddress, string $userAgent): void
    {
        $this->recordDomainEvent(new UserLoggedIn(
            userId: $this->id,
            provider: $provider,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            occurredAt: date('c'),
        ));
    }
}

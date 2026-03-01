<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Entities;

use Modules\Auth\Domain\ValueObjects\UserEmail;

/**
 * User — Domain Entity (Aggregate Root)
 * 
 * Agnostic of Eloquent / infrastructure.
 */
final readonly class User
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
    ) {
    }

    /**
     * @return UserEmail|null
     */
    public function getEmailValueObject(): ?UserEmail
    {
        return $this->email ? new UserEmail($this->email) : null;
    }
}

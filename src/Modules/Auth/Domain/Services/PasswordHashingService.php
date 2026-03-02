<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Services;

use Modules\Auth\Domain\ValueObjects\Password;

/**
 * PasswordHashingService — Domain service for password hashing operations.
 * 
 * Features:
 * - Secure hashing with Argon2id
 * - Password verification
 * - Rehashing detection
 */
final readonly class PasswordHashingService
{
    #[\NoDiscard]
    public function hash(Password $password): string
    {
        return $password->hash();
    }

    #[\NoDiscard]
    public function verify(Password $password, string $hashedPassword): bool
    {
        return $password->verify($hashedPassword);
    }

    #[\NoDiscard]
    public function needsRehash(string $hashedPassword): bool
    {
        return password_needs_rehash($hashedPassword, PASSWORD_ARGON2ID);
    }

    #[\NoDiscard]
    public function rehash(Password $password): string
    {
        return $this->hash($password);
    }
}

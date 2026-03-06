<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Ports;

use Modules\Auth\Domain\Entities\User;
use Modules\Auth\Domain\ValueObjects\UserEmail;

/**
 * UserRepositoryPort — Port for user persistence operations.
 *
 * Implementations live in Infrastructure/Persistence/Repositories/.
 */
interface UserRepositoryPort
{
    public function findByEmail(UserEmail $email): ?User;

    public function findByEmailOrPhone(string $identifier): ?User;

    public function findByUsername(string $username): ?User;

    public function findById(int $id): ?User;

    public function getPasswordHashById(int $id): ?string;

    public function create(array $data): User;

    public function update(User $user, array $data): User;
}

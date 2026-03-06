<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Repositories;

use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;
use Modules\Auth\Domain\Entities\User;
use Modules\Auth\Domain\Ports\UserRepositoryPort;
use Modules\Auth\Domain\ValueObjects\UserEmail;
use Modules\Auth\Infrastructure\Persistence\Mappers\UserMapper;

/**
 * EloquentUserRepository — Eloquent adapter implementing UserRepositoryPort.
 */
final class EloquentUserRepository implements UserRepositoryPort
{
    public function findByEmail(UserEmail $email): ?User
    {
        $eloquentUser = UserEloquentModel::where('email', $email->value)->first();
        return $eloquentUser ? UserMapper::toDomain($eloquentUser) : null;
    }

    public function findByEmailOrPhone(string $identifier): ?User
    {
        $eloquentUser = UserEloquentModel::query()
            ->where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        return $eloquentUser ? UserMapper::toDomain($eloquentUser) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $eloquentUser = UserEloquentModel::query()
            ->where('username', $username)
            ->first();

        return $eloquentUser ? UserMapper::toDomain($eloquentUser) : null;
    }

    public function findById(int $id): ?User
    {
        $eloquentUser = UserEloquentModel::find($id);
        return $eloquentUser ? UserMapper::toDomain($eloquentUser) : null;
    }

    public function getPasswordHashById(int $id): ?string
    {
        /** @var string|null $password */
        $password = UserEloquentModel::query()
            ->whereKey($id)
            ->value('password');

        return $password;
    }

    public function create(array $data): User
    {
        $eloquentUser = UserEloquentModel::create($data);
        return UserMapper::toDomain($eloquentUser);
    }

    public function update(User $user, array $data): User
    {
        $eloquentUser = UserEloquentModel::find($user->id);
        if ($eloquentUser) {
            $eloquentUser->update($data);
            return UserMapper::toDomain($eloquentUser->fresh() ?? $eloquentUser);
        }
        return $user;
    }
}

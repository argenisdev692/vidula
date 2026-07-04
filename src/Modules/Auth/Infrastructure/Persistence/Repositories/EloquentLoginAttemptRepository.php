<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Repositories;

use Modules\Auth\Domain\Ports\LoginAttemptRepositoryPort;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\LoginAttemptEloquentModel;

final readonly class EloquentLoginAttemptRepository implements LoginAttemptRepositoryPort
{
    public function record(
        string $email,
        ?string $ipAddress,
        ?string $userAgent,
        bool $successful,
        ?string $userUuid = null,
        string $guard = 'web',
        ?string $country = null,
    ): void {
        LoginAttemptEloquentModel::query()->create([
            'email' => $email,
            'ip_address' => $ipAddress,
            'country' => $country,
            'user_agent' => $userAgent,
            'successful' => $successful,
            'user_uuid' => $userUuid,
            'guard' => $guard,
        ]);
    }
}

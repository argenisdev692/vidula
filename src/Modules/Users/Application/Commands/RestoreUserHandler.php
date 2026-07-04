<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands;

use Modules\Users\Domain\Ports\UserRepositoryPort;

final readonly class RestoreUserHandler
{
    public function __construct(private UserRepositoryPort $users) {}

    public function handle(string $uuid): bool
    {
        return $this->users->restore($uuid);
    }
}

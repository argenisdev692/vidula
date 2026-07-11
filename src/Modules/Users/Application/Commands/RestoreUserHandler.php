<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Users\Domain\Ports\UserRepositoryPort;

final readonly class RestoreUserHandler
{
    public function __construct(private UserRepositoryPort $users) {}

    public function handle(string $uuid): bool
    {
        return DB::transaction(fn () => $this->users->restore($uuid));
    }
}

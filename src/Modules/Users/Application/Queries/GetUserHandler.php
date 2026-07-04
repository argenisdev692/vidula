<?php

declare(strict_types=1);

namespace Modules\Users\Application\Queries;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Users\Domain\Ports\UserRepositoryPort;

final readonly class GetUserHandler
{
    public function __construct(private UserRepositoryPort $users) {}

    public function handle(string $uuid): User
    {
        return $this->users->findByUuid($uuid)
            ?? throw (new ModelNotFoundException)->setModel(User::class, [$uuid]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands\CreateUser;

use Modules\Users\Application\DTOs\CreateUserDTO;

final readonly class CreateUserCommand
{
    public function __construct(
        public CreateUserDTO $dto,
    ) {
    }
}

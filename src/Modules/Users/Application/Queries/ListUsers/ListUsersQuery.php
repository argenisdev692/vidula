<?php

declare(strict_types=1);

namespace Modules\Users\Application\Queries\ListUsers;

use Modules\Users\Application\DTOs\UserFilterDTO;

final readonly class ListUsersQuery
{
    public function __construct(
        public UserFilterDTO $filters,
    ) {
    }
}

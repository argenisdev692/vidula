<?php

declare(strict_types=1);

namespace Modules\Users\Application\Queries\GetUser;

/**
 * GetUserQuery — Fetch a single user by UUID.
 */
final readonly class GetUserQuery
{
    public function __construct(
        public string $uuid,
    ) {
    }
}

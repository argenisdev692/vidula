<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Queries\GetUser;

/**
 * GetUserQuery — Query to get a single user by ID or UUID.
 */
final readonly class GetUserQuery
{
    public function __construct(
        public ?int $id = null,
        public ?string $uuid = null,
    ) {
        if ($id === null && $uuid === null) {
            throw new \InvalidArgumentException('Either id or uuid must be provided');
        }
    }
}

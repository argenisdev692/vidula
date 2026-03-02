<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Queries\ListUsers;

/**
 * ListUsersQuery — Query to list users with pagination and filters.
 */
final readonly class ListUsersQuery
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 15,
        public ?string $search = null,
        public ?bool $emailVerified = null,
        public ?string $sortBy = 'created_at',
        public string $sortDirection = 'desc',
    ) {}
}

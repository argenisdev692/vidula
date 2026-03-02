<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Events;

/**
 * UserUpdated — Domain event fired when user profile is updated.
 */
final readonly class UserUpdated
{
    public function __construct(
        public int $userId,
        public string $uuid,
        public array $changes,
        public string $occurredAt,
    ) {
    }
}

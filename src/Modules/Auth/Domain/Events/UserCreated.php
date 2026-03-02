<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Events;

/**
 * UserCreated — Domain event fired when a new user is created.
 */
final readonly class UserCreated
{
    public function __construct(
        public string $uuid,
        public string $name,
        public ?string $email,
        public string $occurredAt,
    ) {
    }
}

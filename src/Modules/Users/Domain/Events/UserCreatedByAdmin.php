<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Events;

use Shared\Domain\Events\DomainEvent;

/**
 * UserCreatedByAdmin Domain Event
 */
final readonly class UserCreatedByAdmin extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public string $email,
        public string $setupToken,
        ?string $occurredOn = null
    ) {
        parent::__construct($aggregateId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'user.created_by_admin';
    }
}

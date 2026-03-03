<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Events;

use Shared\Domain\Events\DomainEvent;

/**
 * UserSuspended Domain Event
 */
final readonly class UserSuspended extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public string $reason = '',
        ?string $occurredOn = null
    ) {
        parent::__construct($aggregateId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'user.suspended';
    }
}

<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Events;

use Shared\Domain\Events\DomainEvent;

/**
 * UserSuspended Domain Event
 */
final class UserSuspended extends DomainEvent
{
    public function __construct(
        public string $aggregateId,
        public string $reason = '',
        public string $occurredOn = ''
    ) {
        parent::__construct($aggregateId, $occurredOn ?: date('Y-m-d H:i:s'));
    }

    public static function eventName(): string
    {
        return 'user.suspended';
    }
}

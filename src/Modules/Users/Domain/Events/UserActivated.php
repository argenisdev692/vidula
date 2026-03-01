<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Events;

use Shared\Domain\Events\DomainEvent;

/**
 * UserActivated Domain Event
 */
final class UserActivated extends DomainEvent
{
    public function __construct(
        public string $aggregateId,
        public string $occurredOn = ''
    ) {
        parent::__construct($aggregateId, $occurredOn ?: date('Y-m-d H:i:s'));
    }

    public static function eventName(): string
    {
        return 'user.activated';
    }
}

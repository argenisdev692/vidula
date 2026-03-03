<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Events;

use Shared\Domain\Events\DomainEvent;

/**
 * UserActivated Domain Event
 */
final readonly class UserActivated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        ?string $occurredOn = null
    ) {
        parent::__construct($aggregateId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'user.activated';
    }
}

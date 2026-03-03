<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Events;

use Shared\Domain\Events\DomainEvent;

/**
 * ProfileUpdated Domain Event
 */
final readonly class ProfileUpdated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public array $changedFields,
        ?string $occurredOn = null
    ) {
        parent::__construct($aggregateId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'user.profile_updated';
    }
}

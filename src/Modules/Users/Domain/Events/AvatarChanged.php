<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Events;

use Shared\Domain\Events\DomainEvent;

/**
 * AvatarChanged Domain Event
 */
final readonly class AvatarChanged extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public string $newPath,
        ?string $occurredOn = null
    ) {
        parent::__construct($aggregateId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'user.avatar_changed';
    }
}

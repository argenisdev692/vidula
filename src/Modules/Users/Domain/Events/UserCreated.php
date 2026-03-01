<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Events;

use Shared\Domain\Events\DomainEvent;

final readonly class UserCreated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public string $email,
        public string $name,
        ?string $occurredOn = null
    ) {
        parent::__construct($aggregateId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'user.created';
    }
}

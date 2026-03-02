<?php

declare(strict_types=1);

namespace Modules\Students\Domain\Events;

use Shared\Domain\Events\DomainEvent;

final readonly class StudentUpdated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public string $name,
        ?string $occurredOn = null
    ) {
        parent::__construct($aggregateId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'student.updated';
    }
}

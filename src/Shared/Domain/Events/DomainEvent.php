<?php

declare(strict_types=1);

namespace Shared\Domain\Events;

use DateTimeImmutable;

abstract readonly class DomainEvent
{
    public DateTimeImmutable $occurredOn;

    public function __construct(
        public string $aggregateId,
        ?string $occurredOn = null
    ) {
        $this->occurredOn = $occurredOn
            ? new DateTimeImmutable($occurredOn)
            : new DateTimeImmutable();
    }

    abstract public static function eventName(): string;
}

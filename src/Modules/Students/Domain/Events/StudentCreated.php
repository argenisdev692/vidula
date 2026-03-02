<?php

declare(strict_types=1);

namespace Modules\Students\Domain\Events;

use Shared\Domain\Events\DomainEvent;

final readonly class StudentCreated extends DomainEvent
{
    public function __construct(
        public string $aggregateId,
        public string $name,
        public string $occurredOn
    ) {}

    public static function eventName(): string
    {
        return 'student.created';
    }

    #[\NoDiscard]
    public function toPrimitives(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'name' => $this->name,
            'occurredOn' => $this->occurredOn,
        ];
    }
}

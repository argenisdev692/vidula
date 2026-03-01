<?php

declare(strict_types=1);

namespace Shared\Domain\Entities;

/**
 * AggregateRoot — Base class for all domain aggregates.
 */
abstract class AggregateRoot
{
    /**
     * @var array<object> List of domain events to be dispatched.
     */
    private array $domainEvents = [];

    /**
     * Record a domain event.
     *
     * @param object $event
     * @return void
     */
    protected function recordDomainEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * Pull and clear domain events.
     *
     * @return array<object>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}

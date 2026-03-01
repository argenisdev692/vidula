<?php

declare(strict_types=1);

namespace Shared\Domain\Events;

/**
 * DomainEventPublisher — In-memory synchronous event dispatcher.
 */
final class DomainEventPublisher
{
    private static ?self $instance = null;
    /** @var list<object> */
    private array $subscribers = [];

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function subscribe(object $subscriber): void
    {
        $this->subscribers[] = $subscriber;
    }

    public function publish(DomainEvent $event): void
    {
        // Simple synchronous dispatch for now
        // In a real CQRS system, this might push to a Bus
        event($event);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Events;

use Shared\Domain\Events\DomainEvent;

final readonly class ProductCreated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public string $title,
        ?string $occurredOn = null
    ) {
        parent::__construct($aggregateId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'product.created';
    }

    #[\NoDiscard]
    public function toPrimitives(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'title' => $this->title,
            'occurredOn' => $this->occurredOn->format('c'),
        ];
    }
}

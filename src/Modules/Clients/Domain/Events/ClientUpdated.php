<?php

declare(strict_types=1);

namespace Modules\Client\Domain\Events;

use Shared\Domain\Events\DomainEvent;

final readonly class ClientUpdated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public string $companyName,
        ?string $occurredOn = null
    ) {
        parent::__construct($aggregateId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'client.updated';
    }
}

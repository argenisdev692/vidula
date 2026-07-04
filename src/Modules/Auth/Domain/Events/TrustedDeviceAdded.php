<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Events;

final readonly class TrustedDeviceAdded
{
    public function __construct(
        public string $userUuid,
        public string $deviceUuid,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable,
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Events;

final readonly class SocialAccountLinked
{
    public function __construct(
        public string $userUuid,
        public string $provider,
        public bool $newUser,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable,
    ) {}
}

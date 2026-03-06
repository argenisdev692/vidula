<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Events;

/**
 * OtpGenerated — Fired when a new OTP is created for a user.
 */
final readonly class OtpGenerated
{
    public function __construct(
        public string $identifier,
        public string $channel,
        public string $occurredAt,
    ) {
    }
}

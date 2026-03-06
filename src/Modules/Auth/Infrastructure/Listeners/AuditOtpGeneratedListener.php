<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Listeners;

use Modules\Auth\Domain\Events\OtpGenerated;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class AuditOtpGeneratedListener
{
    public function __construct(
        private AuditInterface $audit,
    ) {
    }

    public function handle(OtpGenerated $event): void
    {
        $this->audit->log(
            'auth',
            'Auth OTP generated',
            [
                'channel' => $event->channel,
                'identifier' => $this->maskIdentifier($event->identifier),
                'occurred_at' => $event->occurredAt,
            ],
        );
    }

    private function maskIdentifier(string $identifier): string
    {
        if (str_contains($identifier, '@')) {
            [$localPart, $domain] = explode('@', $identifier, 2);
            $visible = substr($localPart, 0, 2);

            return $visible . str_repeat('*', max(strlen($localPart) - 2, 0)) . '@' . $domain;
        }

        $visible = substr($identifier, -2);

        return str_repeat('*', max(strlen($identifier) - 2, 0)) . $visible;
    }
}

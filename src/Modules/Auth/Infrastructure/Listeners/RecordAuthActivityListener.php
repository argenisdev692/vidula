<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Listeners;

use Modules\Auth\Domain\Events\OtpGenerated;
use Modules\Auth\Domain\Events\UserLoggedIn;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class RecordAuthActivityListener
{
    public function __construct(
        private AuditInterface $audit,
    ) {
    }

    public function handle(UserLoggedIn|OtpGenerated $event): void
    {
        if ($event instanceof UserLoggedIn) {
            $this->audit->log(
                logName: 'auth.user_logged_in',
                description: 'User authenticated successfully',
                properties: [
                    'user_id' => $event->userId,
                    'provider' => $event->provider,
                    'ip_address' => $event->ipAddress,
                    'user_agent' => $event->userAgent,
                    'occurred_at' => $event->occurredAt,
                ],
            );

            return;
        }

        $this->audit->log(
            logName: 'auth.otp_generated',
            description: 'OTP generated for authentication flow',
            properties: [
                'identifier' => $this->maskIdentifier($event->identifier),
                'channel' => $event->channel,
                'occurred_at' => $event->occurredAt,
            ],
        );
    }

    private function maskIdentifier(string $identifier): string
    {
        if (str_contains($identifier, '@')) {
            [$local, $domain] = explode('@', $identifier, 2);
            $visible = substr($local, 0, min(2, strlen($local)));
            $masked = str_repeat('*', max(1, strlen($local) - strlen($visible)));

            return $visible . $masked . '@' . $domain;
        }

        if (strlen($identifier) <= 4) {
            return str_repeat('*', strlen($identifier));
        }

        return str_repeat('*', strlen($identifier) - 4) . substr($identifier, -4);
    }
}

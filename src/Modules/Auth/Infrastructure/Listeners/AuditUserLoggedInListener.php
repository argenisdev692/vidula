<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Listeners;

use Modules\Auth\Domain\Events\UserLoggedIn;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class AuditUserLoggedInListener
{
    public function __construct(
        private AuditInterface $audit,
    ) {
    }

    public function handle(UserLoggedIn $event): void
    {
        $this->audit->log(
            'auth',
            'Auth user logged in',
            [
                'user_id' => $event->userId,
                'provider' => $event->provider,
                'ip_address' => $event->ipAddress,
                'occurred_at' => $event->occurredAt,
            ],
        );
    }
}

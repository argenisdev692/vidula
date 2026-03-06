<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Listeners;

use Modules\Auth\Domain\Events\PasswordChanged;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class AuditPasswordChangedListener
{
    public function __construct(
        private AuditInterface $audit,
    ) {
    }

    public function handle(PasswordChanged $event): void
    {
        $this->audit->log(
            'auth',
            'Auth password changed',
            [
                'user_id' => $event->userId,
                'method' => $event->method,
                'occurred_at' => $event->occurredAt,
            ],
        );
    }
}

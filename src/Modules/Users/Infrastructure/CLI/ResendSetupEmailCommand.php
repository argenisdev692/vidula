<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\CLI;

use Illuminate\Console\Command;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Modules\Users\Domain\Events\UserCreatedByAdmin;
use Shared\Domain\Events\DomainEventPublisher;

/**
 * ResendSetupEmailCommand
 * 
 * Usage: php artisan users:resend-setup {email}
 */
final class ResendSetupEmailCommand extends Command
{
    protected $signature = 'users:resend-setup {email}';
    protected $description = 'Resend the account setup email to a pending user';

    public function handle(UserRepositoryPort $repository): int
    {
        $email = $this->argument('email');
        $user = $repository->findByEmail($email);

        if (!$user) {
            $this->error("User with email [{$email}] not found.");
            return 1;
        }

        if ($user->status->value !== 'pending_setup' || !$user->setupToken) {
            $this->error("User is not in pending_setup state.");
            return 1;
        }

        DomainEventPublisher::instance()->publish(
            new UserCreatedByAdmin(
                aggregateId: $user->uuid,
                email: $user->email,
                setupToken: $user->setupToken,
                occurredOn: now()->toDateTimeString()
            )
        );

        $this->info("Setup event dispatched for [{$email}].");

        return 0;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands;

use App\Models\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Modules\Users\Application\DTOs\ActivateAccountData;
use Modules\Users\Domain\Events\UserActivated;

/**
 * Completes activation: sets the chosen password (Argon2id via the model's
 * `hashed` cast) and verifies the email in the SAME step — clicking the signed
 * link already proves email ownership, so no second verification mail is sent.
 *
 * Idempotency / single-use is enforced by the caller: only a Pending user
 * (null password) reaches this handler.
 */
final readonly class ActivateAccountHandler
{
    public function __construct(private Dispatcher $events) {}

    public function handle(User $user, ActivateAccountData $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            $user->forceFill([
                'password' => $data->password, // hashed by the model cast
                'invited_at' => null,
            ])->save();

            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }
        });

        $this->events->dispatch(new UserActivated($user->uuid, $user->email));
    }
}

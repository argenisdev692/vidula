<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Auth\Social;

use App\Models\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Auth\Domain\Events\SocialAccountLinked;
use Modules\Auth\Domain\Exceptions\SocialAuthException;
use Modules\Auth\Domain\ValueObjects\SocialUserData;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\LinkedSocialAccountEloquentModel;
use Shared\Domain\Ports\AuditPort;

/**
 * Resolves an OAuth identity to a local User, applying the account-takeover
 * guard (prompt §4). Tightly coupled to the Eloquent User aggregate, so it
 * lives in Infrastructure (an adapter for "authenticate via external identity")
 * rather than as a pure Application handler.
 *
 * Resolution order:
 *   1. Known link  -> that user.
 *   2. Email match -> link ONLY if BOTH the provider email and the local email
 *      are verified (otherwise refuse: prevents hijacking an account by
 *      registering its email at a provider).
 *   3. Otherwise    -> create a new, pre-verified user.
 */
final readonly class SocialAccountResolver
{
    public function __construct(
        private AuditPort $audit,
        private Dispatcher $events,
    ) {}

    public function resolve(SocialUserData $social): User
    {
        $existing = LinkedSocialAccountEloquentModel::query()
            ->with('user')
            ->where('provider', $social->provider)
            ->where('provider_user_id', $social->providerUserId)
            ->first();

        if ($existing !== null) {
            $this->refreshTokens($existing, $social);
            /** @var User $user */
            $user = $existing->user;
            $this->record('auth.social_login', $user, $social, false);

            return $user;
        }

        if ($social->email !== null) {
            $user = User::query()->where('email', $social->email)->first();

            if ($user !== null) {
                if (! $social->emailVerified || ! $user->hasVerifiedEmail()) {
                    throw SocialAuthException::linkRefused();
                }

                $this->createLink($user, $social);
                $this->record('auth.social_linked', $user, $social, false);

                return $user;
            }
        }

        if ($social->email === null) {
            throw SocialAuthException::noEmailProvided();
        }

        $user = DB::transaction(function () use ($social): User {
            $user = $this->createUser($social);
            $this->createLink($user, $social);

            return $user;
        });

        $this->record('auth.social_registered', $user, $social, true);

        return $user;
    }

    private function createUser(SocialUserData $social): User
    {
        $user = User::query()->create([
            'first_name' => $social->name ?? $social->nickname ?? Str::before((string) $social->email, '@'),
            'email' => $social->email,
            // Provider already verified the email.
            'email_verified_at' => now(),
            // Random unknown password; the user signs in via the provider or OTP.
            'password' => Hash::make(Str::password(32)),
            'password_changed_at' => now(),
            'terms_and_conditions' => true,
        ]);

        $user->assignRole('USER');

        return $user;
    }

    private function createLink(User $user, SocialUserData $social): LinkedSocialAccountEloquentModel
    {
        return $user->socialAccounts()->create([
            'provider' => $social->provider,
            'provider_user_id' => $social->providerUserId,
            'provider_email' => $social->email,
            'avatar' => $social->avatar,
            'token' => $social->token,
            'refresh_token' => $social->refreshToken,
            'token_expires_at' => $social->expiresIn !== null ? now()->addSeconds($social->expiresIn) : null,
        ]);
    }

    private function refreshTokens(LinkedSocialAccountEloquentModel $link, SocialUserData $social): void
    {
        $link->forceFill([
            'token' => $social->token,
            'refresh_token' => $social->refreshToken,
            'token_expires_at' => $social->expiresIn !== null ? now()->addSeconds($social->expiresIn) : null,
            'avatar' => $social->avatar,
        ])->save();
    }

    private function record(string $event, User $user, SocialUserData $social, bool $newUser): void
    {
        $this->audit->log(
            event: $event,
            subject: $user,
            properties: ['provider' => $social->provider, 'new_user' => $newUser],
            causer: $user,
            logName: 'auth',
        );

        $this->events->dispatch(new SocialAccountLinked((string) $user->uuid, $social->provider, $newUser));
    }
}

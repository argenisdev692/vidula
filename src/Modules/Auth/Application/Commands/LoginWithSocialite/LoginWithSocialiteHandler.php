<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands\LoginWithSocialite;

use Modules\Auth\Domain\Entities\User;
use Illuminate\Support\Str;
use Modules\Auth\Domain\Events\UserLoggedIn;
use Modules\Auth\Domain\Ports\SocialiteRepositoryPort;
use Modules\Auth\Domain\Ports\UserRepositoryPort;

/**
 * LoginWithSocialiteHandler — Orchestrates OAuth find-or-create logic.
 */
final readonly class LoginWithSocialiteHandler
{
    public function __construct(
        private UserRepositoryPort $userRepository,
        private SocialiteRepositoryPort $socialiteRepository,
    ) {
    }

    /**
     * @return array{user: User, event: UserLoggedIn}
     */
    public function handle(LoginWithSocialiteCommand $command): array
    {
        // ── Case 1: Existing provider link ──
        $link = $this->socialiteRepository->findByProviderAndId(
            $command->provider,
            $command->providerId,
        );

        if ($link !== null) {
            $this->socialiteRepository->updateTokens($link, [
                'token' => $command->token,
                'refresh_token' => $command->refreshToken ?? $link->refreshToken,
                'avatar' => $command->avatar,
                'nickname' => $command->nickname,
                'token_expires_at' => $command->expiresIn
                    ? now()->addSeconds($command->expiresIn)
                    : $link->tokenExpiresAt,
            ]);

            $user = $this->userRepository->findById($link->userId);

            if ($user !== null) {
                return ['user' => $user, 'event' => $this->buildEvent($user, $command)];
            }
        }

        // ── Case 2: User exists by email ──
        $user = $command->email
            ? $this->userRepository->findByEmailOrPhone($command->email)
            : null;

        if ($user !== null) {
            $this->createProviderLink($user, $command);
            return ['user' => $user, 'event' => $this->buildEvent($user, $command)];
        }

        // ── Case 3: New user ──
        $nameParts = explode(' ', $command->name ?? 'User', 2);

        $user = $this->userRepository->create([
            'uuid' => (string) Str::uuid(),
            'name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? null,
            'email' => $command->email,
            'email_verified_at' => now(),
            'username' => $this->generateUsername($command),
            'profile_photo_path' => $command->avatar,
            'password' => null,
        ]);

        $this->createProviderLink($user, $command);

        return ['user' => $user, 'event' => $this->buildEvent($user, $command)];
    }

    private function createProviderLink(User $user, LoginWithSocialiteCommand $command): void
    {
        $this->socialiteRepository->createLink($user, $command->provider, [
            'provider_id' => $command->providerId,
            'provider_email' => $command->email,
            'nickname' => $command->nickname,
            'avatar' => $command->avatar,
            'token' => $command->token,
            'refresh_token' => $command->refreshToken,
            'token_expires_at' => $command->expiresIn
                ? now()->addSeconds($command->expiresIn)
                : null,
        ]);
    }

    private function buildEvent(User $user, LoginWithSocialiteCommand $command): UserLoggedIn
    {
        $user->logIn(
            provider: $command->provider,
            ipAddress: $command->ipAddress,
            userAgent: $command->userAgent,
        );

        return $user->pullDomainEvents()[0];
    }

    private function generateUsername(LoginWithSocialiteCommand $command): string
    {
        $base = $command->nickname
            ?? Str::before($command->email ?? 'user', '@');

        $base = Str::slug($base, '_');
        $username = $base;
        $counter = 1;

        while ($this->userRepository->findByEmailOrPhone($username) !== null) {
            $username = $base . '_' . $counter;
            $counter++;
        }

        return $username;
    }
}

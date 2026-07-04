<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Auth\Social;

use Laravel\Socialite\Contracts\User as SocialiteUser;
use Modules\Auth\Domain\ValueObjects\SocialUserData;

/**
 * Maps a Socialite provider user onto the Domain SocialUserData VO.
 *
 * Email verification: Socialite's Google and GitHub drivers only return the
 * provider-verified (primary) email, so a non-null email is treated as
 * verified. This is the account-takeover guard input.
 */
final readonly class SocialiteUserMapper
{
    public function map(string $provider, SocialiteUser $user): SocialUserData
    {
        return new SocialUserData(
            provider: $provider,
            providerUserId: (string) $user->getId(),
            email: $user->getEmail(),
            name: $user->getName(),
            nickname: $user->getNickname(),
            avatar: $user->getAvatar(),
            token: $user->token ?? null,
            refreshToken: $user->refreshToken ?? null,
            expiresIn: isset($user->expiresIn) ? (int) $user->expiresIn : null,
            emailVerified: $user->getEmail() !== null,
        );
    }
}

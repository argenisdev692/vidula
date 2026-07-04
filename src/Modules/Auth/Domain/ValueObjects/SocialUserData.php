<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObjects;

/**
 * Normalized identity returned by an OAuth provider, decoupled from Socialite.
 * Plain readonly value object (NOT a request DTO) so it can live in Domain and
 * be consumed by both the resolver and the persistence layer.
 */
final readonly class SocialUserData
{
    public function __construct(
        public string $provider,
        public string $providerUserId,
        public ?string $email,
        public ?string $name,
        public ?string $nickname,
        public ?string $avatar,
        public ?string $token,
        public ?string $refreshToken,
        public ?int $expiresIn,
        public bool $emailVerified,
    ) {}
}

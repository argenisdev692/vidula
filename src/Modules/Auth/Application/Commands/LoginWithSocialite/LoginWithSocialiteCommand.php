<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands\LoginWithSocialite;

/**
 * LoginWithSocialiteCommand — OAuth callback data for social login.
 */
readonly class LoginWithSocialiteCommand
{
    public function __construct(
        public string $provider,
        public string $providerId,
        public ?string $email,
        public ?string $name,
        public ?string $nickname,
        public ?string $avatar,
        public string $token,
        public ?string $refreshToken,
        public ?int $expiresIn,
        public string $ipAddress,
        public string $userAgent,
    ) {
    }
}

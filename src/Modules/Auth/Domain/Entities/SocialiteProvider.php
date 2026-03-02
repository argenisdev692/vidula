<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Entities;

/**
 * SocialiteProvider — Domain Entity for OAuth links.
 */
final class SocialiteProvider
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $provider,
        public string $providerId,
        public ?string $token = null,
        public ?string $refreshToken = null,
        public ?string $tokenExpiresAt = null,
        public string $createdAt = '',
        public string $updatedAt = '',
        public ?string $deletedAt = null,
    ) {
    }
}

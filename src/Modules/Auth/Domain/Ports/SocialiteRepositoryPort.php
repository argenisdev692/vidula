<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Ports;

use Modules\Auth\Domain\Entities\SocialiteProvider;
use Modules\Auth\Domain\Entities\User;

/**
 * SocialiteRepositoryPort — Port for OAuth provider link persistence.
 */
interface SocialiteRepositoryPort
{
    public function findByProviderAndId(string $provider, string $providerId): ?SocialiteProvider;

    public function createLink(User $user, string $provider, array $data): SocialiteProvider;

    public function updateTokens(SocialiteProvider $link, array $data): void;
}

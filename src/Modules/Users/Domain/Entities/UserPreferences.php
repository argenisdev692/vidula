<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Entities;

use Modules\Users\Domain\ValueObjects\UserId;

/**
 * UserPreferences — Domain Entity
 */
final readonly class UserPreferences
{
    public function __construct(
        public UserId $userId,
        public string $locale = 'en',
        public string $timezone = 'UTC',
        public bool $marketingNotifications = true,
        public array $themeSettings = ['mode' => 'dark']
    ) {
    }
}

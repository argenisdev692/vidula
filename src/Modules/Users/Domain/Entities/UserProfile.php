<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Entities;

use Modules\Users\Domain\Enums\ProfileVisibility;
use Modules\Users\Domain\ValueObjects\Avatar;
use Modules\Users\Domain\ValueObjects\Bio;
use Modules\Users\Domain\ValueObjects\SocialLinks;
use Modules\Users\Domain\ValueObjects\UserId;

/**
 * UserProfile — Domain Entity
 */
final readonly class UserProfile
{
    public function __construct(
        public UserId $userId,
        public Bio $bio,
        public Avatar $avatar,
        public SocialLinks $socialLinks,
        public ProfileVisibility $visibility = ProfileVisibility::Public
    ) {
    }
}

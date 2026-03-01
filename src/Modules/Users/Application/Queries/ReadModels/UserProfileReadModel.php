<?php

declare(strict_types=1);

namespace Modules\Users\Application\Queries\ReadModels;

use Spatie\LaravelData\Data;

/**
 * UserProfileReadModel
 */
final class UserProfileReadModel extends Data
{
    public function __construct(
        public string $userUuid,
        public ?string $bio,
        public ?string $avatarUrl,
        public array $socialLinks,
        public string $visibility,
    ) {
    }
}

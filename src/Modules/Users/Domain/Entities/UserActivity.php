<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Entities;

use Modules\Users\Domain\ValueObjects\UserId;

/**
 * UserActivity — Domain Entity
 *
 * Tracks specific business activities of the user.
 */
final readonly class UserActivity
{
    public function __construct(
        public string $id,
        public UserId $userId,
        public string $action,
        public string $description,
        public array $metadata = [],
        public string $ipAddress = '127.0.0.1',
        public string $createdAt = ''
    ) {
    }
}

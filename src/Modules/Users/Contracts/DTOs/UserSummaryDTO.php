<?php

declare(strict_types=1);

namespace Modules\Users\Contracts\DTOs;

use Spatie\LaravelData\Data;

/**
 * UserSummaryDTO — Published language for other contexts.
 */
final class UserSummaryDTO extends Data
{
    public function __construct(
        public string $uuid,
        public string $fullName,
        public string $email,
        public ?string $avatarUrl = null,
        public string $status
    ) {
    }
}

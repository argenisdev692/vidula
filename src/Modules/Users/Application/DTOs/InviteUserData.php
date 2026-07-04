<?php

declare(strict_types=1);

namespace Modules\Users\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Admin payload to invite a new user. NO password field — the invitee sets it
 * themselves through the activation link (set-password + verify in one step).
 */
#[MapInputName(SnakeCaseMapper::class)]
final class InviteUserData extends Data
{
    public function __construct(
        #[Required, Max(255)]
        public string $firstName,

        #[Nullable, Max(255)]
        public ?string $lastName,

        #[Required, Email, Max(255), Unique('users', 'email')]
        public string $email,

        #[Nullable, Max(255), Unique('users', 'username')]
        public ?string $username = null,

        #[Nullable, Max(50)]
        public ?string $phone = null,
    ) {}
}

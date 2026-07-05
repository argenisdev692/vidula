<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

use App\Models\User;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Response read model for the authenticated user (the /api/auth/me endpoint).
 * Allowlist only (OWASP API3:2023) — never serialize the raw Eloquent model.
 * Authorization on the frontend uses `permissions`, never `roles`.
 *
 * @property array<int, string> $roles
 * @property array<int, string> $permissions
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class AuthUserData extends Data
{
    public function __construct(
        public string $uuid,
        public string $firstName,
        public ?string $lastName,
        public ?string $username,
        public string $email,
        #[MapOutputName('address_2')]
        public ?string $address2,
        public bool $emailVerified,
        public bool $twoFactorEnabled,
        /** @var array<int, string> */
        public array $roles,
        /** @var array<int, string> */
        public array $permissions,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            uuid: (string) $user->uuid,
            firstName: (string) $user->first_name,
            lastName: $user->last_name,
            username: $user->username,
            email: (string) $user->email,
            address2: $user->address_2,
            emailVerified: $user->hasVerifiedEmail(),
            twoFactorEnabled: $user->two_factor_confirmed_at !== null,
            roles: $user->getRoleNames()->all(),
            permissions: $user->getAllPermissions()->pluck('name')->all(),
        );
    }
}

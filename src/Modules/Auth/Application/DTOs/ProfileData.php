<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

use App\Models\User;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Read model for the self-service profile page (`GET /profile`). Allowlist only
 * (OWASP API3:2023) — never serialize the raw Eloquent model. The signed photo
 * URL is resolved by the controller (StoragePort) and injected here so the DTO
 * stays free of infrastructure concerns. Authorization on the frontend uses
 * `permissions`, never `roles`.
 *
 * @property array<int, string> $roles
 * @property array<int, string> $permissions
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class ProfileData extends Data
{
    public function __construct(
        public string $uuid,
        public string $firstName,
        public ?string $lastName,
        public ?string $username,
        public string $email,
        public ?string $phone,
        public ?string $dateOfBirth,
        public ?string $gender,
        public ?string $address,
        #[MapOutputName('address_2')]
        public ?string $address2,
        public ?string $city,
        public ?string $state,
        public ?string $zipCode,
        public ?string $country,
        public ?string $countryCode,
        public ?float $latitude,
        public ?float $longitude,
        public bool $emailVerified,
        public bool $twoFactorEnabled,
        public ?string $createdAt,
        public ?string $profilePhotoUrl,
        /** @var array<int, string> */
        public array $roles,
        /** @var array<int, string> */
        public array $permissions,
    ) {}

    public static function fromModel(User $user, ?string $profilePhotoUrl): self
    {
        return new self(
            uuid: (string) $user->uuid,
            firstName: (string) $user->first_name,
            lastName: $user->last_name,
            username: $user->username,
            email: (string) $user->email,
            phone: $user->phone,
            dateOfBirth: $user->date_of_birth,
            gender: $user->gender,
            address: $user->address,
            address2: $user->address_2,
            city: $user->city,
            state: $user->state,
            zipCode: $user->zip_code,
            country: $user->country,
            countryCode: $user->country_code,
            latitude: $user->latitude,
            longitude: $user->longitude,
            emailVerified: $user->hasVerifiedEmail(),
            twoFactorEnabled: $user->two_factor_confirmed_at !== null,
            createdAt: $user->created_at?->toIso8601String(),
            profilePhotoUrl: $profilePhotoUrl,
            roles: $user->getRoleNames()->all(),
            permissions: $user->getAllPermissions()->pluck('name')->all(),
        );
    }
}

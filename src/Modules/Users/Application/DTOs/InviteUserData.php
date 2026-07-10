<?php

declare(strict_types=1);

namespace Modules\Users\Application\DTOs;

use Illuminate\Validation\Rule;
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

        #[Nullable]
        public ?string $dateOfBirth = null,

        #[Nullable]
        public ?string $gender = null,

        #[Nullable, Max(255)]
        public ?string $address = null,

        #[MapInputName('address_2'), Nullable, Max(255)]
        public ?string $address2 = null,

        #[Nullable, Max(20)]
        public ?string $zipCode = null,

        #[Nullable, Max(120)]
        public ?string $city = null,

        #[Nullable, Max(120)]
        public ?string $state = null,

        #[Nullable, Max(120)]
        public ?string $country = null,

        #[Nullable]
        public ?string $countryCode = null,

        #[Nullable]
        public ?float $latitude = null,

        #[Nullable]
        public ?float $longitude = null,

        /** @var array<int, string> Role names the invited user is created with (optional). */
        public array $roles = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha', 'uppercase'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'roles' => ['array'],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web')->whereNull('deleted_at'),
            ],
        ];
    }
}

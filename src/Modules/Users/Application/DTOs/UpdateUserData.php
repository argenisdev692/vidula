<?php

declare(strict_types=1);

namespace Modules\Users\Application\DTOs;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Admin payload to update an existing user. Passwords are NEVER set here — that
 * stays with the user. `forcePasswordChange = true` flags the user to be routed
 * through Fortify's update-password form on their next login.
 *
 * Uniqueness ignores the user being edited (resolved from the route `uuid`).
 */
#[MapInputName(SnakeCaseMapper::class)]
final class UpdateUserData extends Data
{
    public function __construct(
        public string $firstName,
        public ?string $lastName,
        public string $email,
        public ?string $username = null,
        public ?string $phone = null,
        public ?string $dateOfBirth = null,
        public ?string $gender = null,
        public ?string $address = null,
        #[MapInputName('address_2')]
        public ?string $address2 = null,
        public ?string $zipCode = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $country = null,
        public ?string $countryCode = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public bool $forcePasswordChange = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $uuid = request()->route('uuid');

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($uuid, 'uuid')],
            'username' => ['nullable', 'string', 'min:8', 'max:15', 'regex:/^[a-zA-Z0-9]+$/', Rule::unique('users', 'username')->ignore($uuid, 'uuid')],
            'phone' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
            'address' => ['nullable', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha', 'uppercase'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'force_password_change' => ['boolean'],
        ];
    }
}

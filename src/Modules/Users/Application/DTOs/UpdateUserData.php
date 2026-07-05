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
        #[MapInputName('address_2')]
        public ?string $address2 = null,
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
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($uuid, 'uuid')],
            'phone' => ['nullable', 'string', 'max:50'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'force_password_change' => ['boolean'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Users\Application\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * CreateUserDTO — Data Transfer Object for user creation.
 *
 * @OA\Schema(
 *     schema="CreateUserDTO",
 *     required={"name", "last_name", "email", "role"},
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="last_name", type="string", maxLength=255),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255),
 *     @OA\Property(property="role", type="string", enum={"USER", "ADMIN", "SUPER_ADMIN", "GUEST"}),
 *     @OA\Property(property="username", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="phone", type="string", maxLength=20, nullable=true),
 *     @OA\Property(property="address", type="string", maxLength=500, nullable=true),
 *     @OA\Property(property="city", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="state", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="country", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="zip_code", type="string", maxLength=20, nullable=true)
 * )
 */
#[MapInputName(SnakeCaseMapper::class)]
final class CreateUserDTO extends Data
{
    public function __construct(
        public string $name,
        public string $lastName,
        public string $email,
        public string $role,
        public ?string $username = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $country = null,
        public ?string $zipCode = null,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Modules\Users\Application\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Modules\Users\Domain\Enums\UserStatus;

/**
 * UpdateUserDTO — Data Transfer Object for user updates.
 *
 * @OA\Schema(
 *     schema="UpdateUserDTO",
 *     @OA\Property(property="name", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="lastName", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255, nullable=true),
 *     @OA\Property(property="username", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="phone", type="string", maxLength=20, nullable=true),
 *     @OA\Property(property="address", type="string", maxLength=500, nullable=true),
 *     @OA\Property(property="city", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="state", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="country", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="zipCode", type="string", maxLength=20, nullable=true),
 *     @OA\Property(property="status", type="string", enum={"active","suspended","banned","deleted","pending_setup"}, nullable=true),
 *     @OA\Property(property="role", type="string", nullable=true)
 * )
 */
#[MapInputName(SnakeCaseMapper::class)]
final class UpdateUserDTO extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $username = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $country = null,
        public ?string $zipCode = null,
        public ?UserStatus $status = null,
        public ?string $role = null,
    ) {
    }
}

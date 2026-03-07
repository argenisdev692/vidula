<?php

declare(strict_types=1);

namespace Modules\Students\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * @OA\Schema(
 *     schema="CreateStudentDTO",
 *     required={"name"},
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string", nullable=true),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="dni", type="string", nullable=true),
 *     @OA\Property(property="birthDate", type="string", nullable=true),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="avatar", type="string", nullable=true),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="status", type="string", default="DRAFT"),
 *     @OA\Property(property="active", type="boolean", default=true)
 * )
 */
#[MapInputName(SnakeCaseMapper::class)]
final class CreateStudentDTO extends Data
{
    public function __construct(
        public string $name,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $dni = null,
        public ?string $birthDate = null,
        public ?string $address = null,
        public ?string $avatar = null,
        public ?string $notes = null,
        public string $status = 'DRAFT',
        public bool $active = true
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Modules\Students\Application\Queries\ReadModels;

use Spatie\LaravelData\Data;

/**
 * @OA\Schema(
 *     schema="StudentReadModel",
 *     @OA\Property(property="uuid", type="string", format="uuid"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string", nullable=true),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="dni", type="string", nullable=true),
 *     @OA\Property(property="birthDate", type="string", nullable=true),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="avatar", type="string", nullable=true),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="status", type="string"),
 *     @OA\Property(property="active", type="boolean"),
 *     @OA\Property(property="createdAt", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updatedAt", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="deletedAt", type="string", format="date-time", nullable=true)
 * )
 */
final class StudentReadModel extends Data
{
    public function __construct(
        public string $uuid,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $dni,
        public ?string $birthDate,
        public ?string $address,
        public ?string $avatar,
        public ?string $notes,
        public string $status,
        public bool $active,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt
    ) {
    }
}

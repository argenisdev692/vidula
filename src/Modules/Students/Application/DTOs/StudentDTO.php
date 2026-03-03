<?php

declare(strict_types=1);

namespace Modules\Students\Application\DTOs;

use Modules\Students\Domain\Entities\Student;
use Spatie\LaravelData\Data;

/**
 * @OA\Schema(
 *     schema="StudentDTO",
 *     @OA\Property(property="id", type="string", format="uuid"),
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
 *     @OA\Property(property="updatedAt", type="string", format="date-time", nullable=true)
 * )
 */
final class StudentDTO extends Data
{
    public function __construct(
        public string $id,
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
    ) {
    }

    #[\NoDiscard]
    public static function fromEntity(Student $entity): self
    {
        return new self(
            id: $entity->id->value,
            name: $entity->name,
            email: $entity->email,
            phone: $entity->phone,
            dni: $entity->dni,
            birthDate: $entity->birthDate,
            address: $entity->address,
            avatar: $entity->avatar,
            notes: $entity->notes,
            status: $entity->status,
            active: $entity->active,
            createdAt: $entity->createdAt,
            updatedAt: $entity->updatedAt,
        );
    }
}

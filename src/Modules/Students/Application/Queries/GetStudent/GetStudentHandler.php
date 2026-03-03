<?php

declare(strict_types=1);

namespace Modules\Students\Application\Queries\GetStudent;

use Illuminate\Support\Facades\Cache;
use Modules\Students\Application\Queries\ReadModels\StudentReadModel;
use Modules\Students\Domain\Exceptions\StudentNotFoundException;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Modules\Students\Domain\ValueObjects\StudentId;

final readonly class GetStudentHandler
{
    public function __construct(
        private StudentRepositoryPort $repository
    ) {
    }

    public function handle(GetStudentQuery $query): StudentReadModel
    {
        $cacheKey = "student_{$query->uuid}";
        $ttl = 60 * 60; // 1 hour

        return Cache::remember($cacheKey, $ttl, function () use ($query): StudentReadModel {
            $student = $this->repository->findById(new StudentId($query->uuid));

            if (null === $student) {
                throw StudentNotFoundException::forId($query->uuid);
            }

            return new StudentReadModel(
                id: $student->id->value,
                name: $student->name,
                email: $student->email,
                phone: $student->phone,
                dni: $student->dni,
                birthDate: $student->birthDate,
                address: $student->address,
                avatar: $student->avatar,
                notes: $student->notes,
                status: $student->status,
                active: $student->active,
                createdAt: $student->createdAt,
                updatedAt: $student->updatedAt,
                deletedAt: $student->deletedAt
            );
        });
    }
}

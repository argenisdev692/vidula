<?php

declare(strict_types=1);

namespace Modules\Enrollments\Application\Queries;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Enrollments\Domain\Ports\EnrollmentRepositoryPort;
use Modules\Enrollments\Infrastructure\Persistence\Eloquent\Models\ClassroomEnrollmentEloquentModel;

final readonly class GetEnrollmentHandler
{
    public function __construct(private EnrollmentRepositoryPort $enrollments) {}

    public function handle(string $uuid): ClassroomEnrollmentEloquentModel
    {
        return $this->enrollments->findByUuid($uuid)
            ?? throw (new ModelNotFoundException)->setModel(ClassroomEnrollmentEloquentModel::class, [$uuid]);
    }
}

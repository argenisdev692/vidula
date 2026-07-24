<?php

declare(strict_types=1);

namespace Modules\Students\Application\Queries;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;

final readonly class GetStudentHandler
{
    public function __construct(private StudentRepositoryPort $students) {}

    public function handle(string $uuid): StudentEloquentModel
    {
        return $this->students->findByUuid($uuid)
            ?? throw (new ModelNotFoundException)->setModel(StudentEloquentModel::class, [$uuid]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Student\Application\Commands\RestoreStudent;

use Modules\Student\Domain\Exceptions\StudentNotFoundException;
use Modules\Student\Domain\Ports\StudentRepositoryPort;
use Modules\Student\Domain\ValueObjects\StudentId;

final readonly class RestoreStudentHandler
{
    public function __construct(
        private StudentRepositoryPort $repository
    ) {
    }

    public function handle(RestoreStudentCommand $command): void
    {
        $id = new StudentId($command->id);
        $this->repository->restore($id);
    }
}

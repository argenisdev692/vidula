<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands\RestoreStudent;

use Modules\Students\Domain\Exceptions\StudentNotFoundException;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Modules\Students\Domain\ValueObjects\StudentId;

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

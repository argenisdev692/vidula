<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands\CreateStudent;

use Modules\Students\Application\DTOs\CreateStudentDTO;

final readonly class CreateStudentCommand
{
    public function __construct(
        public CreateStudentDTO $dto
    ) {}
}

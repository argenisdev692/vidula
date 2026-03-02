<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands\UpdateStudent;

use Modules\Students\Application\DTOs\UpdateStudentDTO;

final readonly class UpdateStudentCommand
{
    public function __construct(
        public string $uuid,
        public UpdateStudentDTO $dto
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Student\Application\Commands\RestoreStudent;

final readonly class RestoreStudentCommand
{
    public function __construct(
        public string $id
    ) {
    }
}

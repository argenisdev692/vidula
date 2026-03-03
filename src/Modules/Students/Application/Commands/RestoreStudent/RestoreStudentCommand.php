<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands\RestoreStudent;

final readonly class RestoreStudentCommand
{
    public function __construct(
        public string $id
    ) {
    }
}

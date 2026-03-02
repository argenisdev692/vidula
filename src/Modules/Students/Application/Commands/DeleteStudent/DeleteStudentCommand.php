<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands\DeleteStudent;

final readonly class DeleteStudentCommand
{
    public function __construct(
        public string $id
    ) {}
}

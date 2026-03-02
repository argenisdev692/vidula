<?php

declare(strict_types=1);

namespace Modules\Students\Application\Queries\GetStudent;

final readonly class GetStudentQuery
{
    public function __construct(
        public string $uuid
    ) {}
}

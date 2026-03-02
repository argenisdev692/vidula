<?php

declare(strict_types=1);

namespace Modules\Students\Application\Queries\ListStudent;

use Modules\Students\Application\DTOs\StudentFilterDTO;

final readonly class ListStudentQuery
{
    public function __construct(
        public StudentFilterDTO $filters
    ) {}
}

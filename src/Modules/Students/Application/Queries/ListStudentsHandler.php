<?php

declare(strict_types=1);

namespace Modules\Students\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Students\Application\DTOs\StudentFilterData;
use Modules\Students\Domain\Ports\StudentRepositoryPort;

final readonly class ListStudentsHandler
{
    public function __construct(private StudentRepositoryPort $students) {}

    public function handle(StudentFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->students->paginate($filters, $perPage);
    }
}

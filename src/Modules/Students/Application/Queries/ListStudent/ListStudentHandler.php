<?php

declare(strict_types=1);

namespace Modules\Students\Application\Queries\ListStudent;

use Illuminate\Support\Facades\Cache;
use Modules\Students\Application\DTOs\StudentFilterDTO;
use Modules\Students\Application\Queries\ReadModels\StudentReadModel;
use Modules\Students\Domain\Ports\StudentRepositoryPort;

final readonly class ListStudentHandler
{
    public function __construct(
        private StudentRepositoryPort $repository
    ) {
    }

    /**
     * @return array{data: list<StudentReadModel>, meta: array{total: int, perPage: int, currentPage: int, lastPage: int}}
     */
    public function handle(ListStudentQuery $query): array
    {
        $filters = $query->filters;
        $cacheKey = "students_list_" . md5(serialize($filters->toArray()));
        $ttl = 60 * 15;

        try {
            return Cache::tags(['students_list'])->remember($cacheKey, $ttl, function () use ($filters): array {
                return $this->fetchData($filters);
            });
        } catch (\Exception $e) {
            return Cache::remember($cacheKey, $ttl, function () use ($filters): array {
                return $this->fetchData($filters);
            });
        }
    }

    /**
     * @return array{data: list<StudentReadModel>, meta: array{total: int, perPage: int, currentPage: int, lastPage: int}}
     */
    private function fetchData(StudentFilterDTO $filters): array
    {
        $result = $this->repository->findAllPaginated(
            filters: $filters->toArray(),
            page: $filters->page,
            perPage: $filters->perPage
        );

        $mapped = $result['data']
            |> (fn($students) => array_map(
                fn($student) => new StudentReadModel(
                    uuid: $student->id->value,
                    name: $student->name,
                    email: $student->email,
                    phone: $student->phone,
                    dni: $student->dni,
                    birthDate: $student->birthDate,
                    address: $student->address,
                    avatar: $student->avatar,
                    notes: $student->notes,
                    status: $student->status,
                    active: $student->active,
                    createdAt: $student->createdAt,
                    updatedAt: $student->updatedAt,
                    deletedAt: $student->deletedAt
                ),
                $students
            ));

        // Wrap pagination into `meta` to match frontend PaginatedResponse<T>
        return [
            'data' => $mapped,
            'meta' => [
                'total' => $result['total'],
                'perPage' => $result['perPage'],
                'currentPage' => $result['currentPage'],
                'lastPage' => $result['lastPage'],
            ],
        ];
    }
}

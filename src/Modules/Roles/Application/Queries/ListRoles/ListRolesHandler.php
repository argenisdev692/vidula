<?php

declare(strict_types=1);

namespace Modules\Roles\Application\Queries\ListRoles;

use Illuminate\Support\Facades\Cache;
use Modules\Roles\Application\DTOs\RoleFilterDTO;
use Modules\Roles\Application\Queries\ReadModels\RoleListReadModel;
use Modules\Roles\Domain\Entities\Role;
use Modules\Roles\Domain\Ports\RoleRepositoryPort;

final readonly class ListRolesHandler
{
    public function __construct(
        private RoleRepositoryPort $repository,
    ) {
    }

    /**
     * @return array{data: list<RoleListReadModel>, meta: array{total: int, perPage: int, currentPage: int, lastPage: int}}
     */
    public function handle(ListRolesQuery $query): array
    {
        $filters = $query->filters;
        $cachePayload = json_encode($filters->toArray()) ?: '[]';
        $cacheKey = 'roles_list_' . md5($cachePayload);
        $ttl = 60 * 15;

        try {
            return Cache::tags(['roles_list'])->remember($cacheKey, $ttl, function () use ($filters): array {
                return $this->fetchAndMapRoles($filters);
            });
        } catch (\Exception) {
            return Cache::remember($cacheKey, $ttl, function () use ($filters): array {
                return $this->fetchAndMapRoles($filters);
            });
        }
    }

    /**
     * @return array{data: list<RoleListReadModel>, meta: array{total: int, perPage: int, currentPage: int, lastPage: int}}
     */
    private function fetchAndMapRoles(RoleFilterDTO $filters): array
    {
        $result = $this->repository->findAllPaginated(
            filters: $filters->toArray(),
            page: $filters->page,
            perPage: $filters->perPage,
        );

        $mapped = $result['data']
            |> (fn (array $roles): array => array_map(self::mapToReadModel(...), $roles));

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

    private static function mapToReadModel(Role $role): RoleListReadModel
    {
        return new RoleListReadModel(
            uuid: $role->uuid,
            name: $role->name,
            guardName: $role->guardName,
            permissions: $role->permissions,
            usersCount: $role->usersCount,
            createdAt: $role->createdAt,
            updatedAt: $role->updatedAt,
        );
    }
}

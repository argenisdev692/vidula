<?php

declare(strict_types=1);

namespace Modules\Permissions\Application\Queries\ListPermissions;

use Illuminate\Support\Facades\Cache;
use Modules\Permissions\Application\Queries\ReadModels\PermissionListReadModel;
use Modules\Permissions\Domain\Ports\PermissionRepositoryPort;

final readonly class ListPermissionsHandler
{
    public function __construct(
        private PermissionRepositoryPort $repository,
    ) {
    }

    public function handle(ListPermissionsQuery $query): array
    {
        $cacheKey = 'permissions_list_' . md5(json_encode([
            'search' => $query->filters->search,
            'guardName' => $query->filters->guardName,
            'sortBy' => $query->filters->sortBy,
            'sortDir' => $query->filters->sortDir,
            'page' => $query->filters->page,
            'perPage' => $query->filters->perPage,
        ], JSON_THROW_ON_ERROR));

        return Cache::remember($cacheKey, 120, function () use ($query): array {
            $result = $this->repository->findAllPaginated(
                filters: [
                    'search' => $query->filters->search,
                    'guardName' => $query->filters->guardName,
                    'sortBy' => $query->filters->sortBy,
                    'sortDir' => $query->filters->sortDir,
                ],
                page: $query->filters->page,
                perPage: $query->filters->perPage,
            );

            return [
                'data' => array_map(
                    static fn ($permission): PermissionListReadModel => new PermissionListReadModel(
                        uuid: $permission->uuid,
                        name: $permission->name,
                        guardName: $permission->guardName,
                        roles: $permission->roles,
                        rolesCount: $permission->rolesCount,
                        createdAt: $permission->createdAt,
                        updatedAt: $permission->updatedAt,
                    ),
                    $result['data'],
                ),
                'meta' => [
                    'total' => $result['total'],
                    'perPage' => $result['perPage'],
                    'currentPage' => $result['currentPage'],
                    'lastPage' => $result['lastPage'],
                ],
            ];
        });
    }
}

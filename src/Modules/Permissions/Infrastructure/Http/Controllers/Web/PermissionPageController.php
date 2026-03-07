<?php

declare(strict_types=1);

namespace Modules\Permissions\Infrastructure\Http\Controllers\Web;

use Inertia\Inertia;
use Inertia\Response;
use Modules\Permissions\Application\Queries\GetPermission\GetPermissionHandler;
use Modules\Permissions\Application\Queries\GetPermission\GetPermissionQuery;
use Modules\Permissions\Domain\Ports\PermissionRepositoryPort;

final class PermissionPageController
{
    public function __construct(
        private readonly GetPermissionHandler $getHandler,
        private readonly PermissionRepositoryPort $repository,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('permissions/PermissionsIndexPage');
    }

    public function create(): Response
    {
        return Inertia::render('permissions/PermissionCreatePage', [
            'available_roles' => $this->repository->listRoles('web'),
        ]);
    }

    public function show(string $uuid): Response
    {
        $permission = $this->getHandler->handle(new GetPermissionQuery($uuid));

        return Inertia::render('permissions/PermissionShowPage', [
            'permission' => $permission,
        ]);
    }

    public function edit(string $uuid): Response
    {
        $permission = $this->getHandler->handle(new GetPermissionQuery($uuid));

        return Inertia::render('permissions/PermissionEditPage', [
            'permission' => $permission,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Roles\Infrastructure\Http\Controllers\Web;

use Inertia\Inertia;
use Inertia\Response;
use Modules\Roles\Application\Queries\GetRole\GetRoleHandler;
use Modules\Roles\Application\Queries\GetRole\GetRoleQuery;
use Modules\Roles\Domain\Ports\RoleRepositoryPort;

final class RolePageController
{
    public function __construct(
        private readonly GetRoleHandler $getHandler,
        private readonly RoleRepositoryPort $repository,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('roles/RolesIndexPage');
    }

    public function create(): Response
    {
        return Inertia::render('roles/RoleCreatePage', [
            'available_permissions' => $this->repository->listPermissions('web'),
        ]);
    }

    public function show(string $uuid): Response
    {
        $role = $this->getHandler->handle(new GetRoleQuery($uuid));

        return Inertia::render('roles/RoleShowPage', [
            'role' => $role,
        ]);
    }

    public function edit(string $uuid): Response
    {
        $role = $this->getHandler->handle(new GetRoleQuery($uuid));

        return Inertia::render('roles/RoleEditPage', [
            'role' => $role,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Authorization\Tests\Unit;

use Mockery;
use Modules\Authorization\Application\Commands\CreateRoleHandler;
use Modules\Authorization\Application\DTOs\RoleData;
use Modules\Authorization\Domain\Ports\RoleRepositoryPort;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\Role;
use PHPUnit\Framework\TestCase;

final class CreateRoleHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_the_role_then_syncs_its_permissions(): void
    {
        // Mockery skips the constructor, so no booted container is needed here.
        $role = Mockery::mock(Role::class);

        $repository = Mockery::mock(RoleRepositoryPort::class);
        $repository->shouldReceive('create')
            ->once()
            ->with(['name' => 'EDITOR', 'guard_name' => 'web'])
            ->andReturn($role);
        $repository->shouldReceive('syncPermissions')
            ->once()
            ->with($role, ['VIEW_ROLES', 'CREATE_ROLES'])
            ->andReturn($role);

        $handler = new CreateRoleHandler($repository);

        $result = $handler->handle(new RoleData(name: '  EDITOR  ', permissions: ['VIEW_ROLES', 'CREATE_ROLES']));

        $this->assertSame($role, $result);
    }
}

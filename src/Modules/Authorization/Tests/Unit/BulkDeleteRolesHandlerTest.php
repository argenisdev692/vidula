<?php

declare(strict_types=1);

namespace Modules\Authorization\Tests\Unit;

use Mockery;
use Modules\Authorization\Application\Commands\BulkDeleteRolesHandler;
use Modules\Authorization\Domain\Exceptions\ProtectedRoleException;
use Modules\Authorization\Domain\Ports\RoleRepositoryPort;
use Modules\Authorization\Domain\SystemRoles;
use Shared\Application\DTOs\BulkUuidsData;
use Tests\TestCase;

final class BulkDeleteRolesHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_soft_deletes_when_no_protected_role_is_in_the_batch(): void
    {
        $uuids = ['11111111-1111-1111-1111-111111111111', '22222222-2222-2222-2222-222222222222'];

        $repository = Mockery::mock(RoleRepositoryPort::class);
        $repository->shouldReceive('firstProtectedName')
            ->once()
            ->with($uuids, SystemRoles::PROTECTED)
            ->andReturn(null);
        $repository->shouldReceive('bulkSoftDeleteByUuid')
            ->once()
            ->with($uuids)
            ->andReturn(2);

        $handler = new BulkDeleteRolesHandler($repository);

        $this->assertSame(2, $handler->handle(new BulkUuidsData(uuids: $uuids)));
    }

    public function test_it_rejects_the_whole_batch_when_it_contains_a_protected_role(): void
    {
        $uuids = ['11111111-1111-1111-1111-111111111111', '33333333-3333-3333-3333-333333333333'];

        $repository = Mockery::mock(RoleRepositoryPort::class);
        $repository->shouldReceive('firstProtectedName')
            ->once()
            ->with($uuids, SystemRoles::PROTECTED)
            ->andReturn('ADMIN');
        // The invariant must short-circuit before any mutation runs.
        $repository->shouldNotReceive('bulkSoftDeleteByUuid');

        $handler = new BulkDeleteRolesHandler($repository);

        $this->expectException(ProtectedRoleException::class);

        $handler->handle(new BulkUuidsData(uuids: $uuids));
    }
}

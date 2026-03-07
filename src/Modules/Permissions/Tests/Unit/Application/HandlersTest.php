<?php

declare(strict_types=1);

namespace Modules\Permissions\Tests\Unit\Application;

use Modules\Permissions\Application\Commands\CreatePermission\CreatePermissionCommand;
use Modules\Permissions\Application\Commands\CreatePermission\CreatePermissionHandler;
use Modules\Permissions\Application\Commands\DeletePermission\DeletePermissionCommand;
use Modules\Permissions\Application\Commands\DeletePermission\DeletePermissionHandler;
use Modules\Permissions\Application\Commands\UpdatePermission\UpdatePermissionCommand;
use Modules\Permissions\Application\Commands\UpdatePermission\UpdatePermissionHandler;
use Modules\Permissions\Application\DTOs\CreatePermissionDTO;
use Modules\Permissions\Application\DTOs\UpdatePermissionDTO;
use Modules\Permissions\Domain\Entities\Permission;
use Modules\Permissions\Domain\Exceptions\PermissionNotFoundException;
use Modules\Permissions\Domain\Ports\PermissionRepositoryPort;
use Modules\Permissions\Domain\ValueObjects\PermissionId;
use PHPUnit\Framework\MockObject\MockObject;
use Shared\Infrastructure\Audit\AuditInterface;
use Tests\TestCase;

final class HandlersTest extends TestCase
{
    /** @var PermissionRepositoryPort&MockObject */
    private PermissionRepositoryPort $mockRepo;

    /** @var AuditInterface&MockObject */
    private AuditInterface $mockAudit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRepo = $this->createMock(PermissionRepositoryPort::class);
        $this->mockAudit = $this->createMock(AuditInterface::class);
    }

    private function makeDummyPermission(string $uuid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee'): Permission
    {
        return new Permission(
            id: new PermissionId(1),
            uuid: $uuid,
            name: 'VIEW_REPORTS',
            guardName: 'web',
            roles: ['SUPER_ADMIN'],
            rolesCount: 1,
        );
    }

    public function test_create_permission_handler_calls_repository_create(): void
    {
        $dto = new CreatePermissionDTO(
            name: 'MANAGE_REPORTS',
            guardName: 'web',
            roles: ['SUPER_ADMIN'],
        );

        $this->mockRepo
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->makeDummyPermission());

        $this->mockAudit
            ->expects($this->once())
            ->method('log');

        $handler = new CreatePermissionHandler($this->mockRepo, $this->mockAudit);
        $result = $handler->handle(new CreatePermissionCommand($dto));

        $this->assertInstanceOf(Permission::class, $result);
    }

    public function test_update_permission_handler_throws_when_permission_not_found(): void
    {
        $this->mockRepo->method('findByUuid')->willReturn(null);

        $handler = new UpdatePermissionHandler($this->mockRepo, $this->mockAudit);
        $dto = new UpdatePermissionDTO(name: 'UPDATED_PERMISSION');

        $this->expectException(PermissionNotFoundException::class);
        $handler->handle(new UpdatePermissionCommand('non-existent-uuid', $dto));
    }

    public function test_delete_permission_handler_calls_delete(): void
    {
        $permission = $this->makeDummyPermission();

        $this->mockRepo->method('findByUuid')->willReturn($permission);
        $this->mockRepo
            ->expects($this->once())
            ->method('delete');

        $this->mockAudit
            ->expects($this->once())
            ->method('log');

        $handler = new DeletePermissionHandler($this->mockRepo, $this->mockAudit);
        $handler->handle(new DeletePermissionCommand($permission->uuid));
    }
}

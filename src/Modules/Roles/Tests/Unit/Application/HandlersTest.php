<?php

declare(strict_types=1);

namespace Modules\Roles\Tests\Unit\Application;

use Modules\Roles\Application\Commands\CreateRole\CreateRoleCommand;
use Modules\Roles\Application\Commands\CreateRole\CreateRoleHandler;
use Modules\Roles\Application\Commands\DeleteRole\DeleteRoleCommand;
use Modules\Roles\Application\Commands\DeleteRole\DeleteRoleHandler;
use Modules\Roles\Application\Commands\UpdateRole\UpdateRoleCommand;
use Modules\Roles\Application\Commands\UpdateRole\UpdateRoleHandler;
use Modules\Roles\Application\DTOs\CreateRoleDTO;
use Modules\Roles\Application\DTOs\UpdateRoleDTO;
use Modules\Roles\Domain\Entities\Role;
use Modules\Roles\Domain\Exceptions\RoleNotFoundException;
use Modules\Roles\Domain\Ports\RoleRepositoryPort;
use Modules\Roles\Domain\ValueObjects\RoleId;
use PHPUnit\Framework\MockObject\MockObject;
use Shared\Infrastructure\Audit\AuditInterface;
use Tests\TestCase;

final class HandlersTest extends TestCase
{
    /** @var RoleRepositoryPort&MockObject */
    private RoleRepositoryPort $mockRepo;

    /** @var AuditInterface&MockObject */
    private AuditInterface $mockAudit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRepo = $this->createMock(RoleRepositoryPort::class);
        $this->mockAudit = $this->createMock(AuditInterface::class);
    }

    private function makeDummyRole(string $uuid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee'): Role
    {
        return new Role(
            id: new RoleId(1),
            uuid: $uuid,
            name: 'ADMIN',
            guardName: 'web',
            permissions: ['VIEW_ROLES'],
            usersCount: 1,
        );
    }

    public function test_create_role_handler_calls_repository_create(): void
    {
        $dto = new CreateRoleDTO(
            name: 'MANAGER',
            guardName: 'web',
            permissions: ['VIEW_ROLES', 'CREATE_ROLES'],
        );

        $this->mockRepo
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->makeDummyRole());

        $this->mockAudit
            ->expects($this->once())
            ->method('log');

        $handler = new CreateRoleHandler($this->mockRepo, $this->mockAudit);
        $result = $handler->handle(new CreateRoleCommand($dto));

        $this->assertInstanceOf(Role::class, $result);
    }

    public function test_update_role_handler_throws_when_role_not_found(): void
    {
        $this->mockRepo->method('findByUuid')->willReturn(null);

        $handler = new UpdateRoleHandler($this->mockRepo, $this->mockAudit);
        $dto = new UpdateRoleDTO(name: 'UPDATED');

        $this->expectException(RoleNotFoundException::class);
        $handler->handle(new UpdateRoleCommand('non-existent-uuid', $dto));
    }

    public function test_update_role_handler_calls_update_when_role_exists(): void
    {
        $role = $this->makeDummyRole();

        $this->mockRepo->method('findByUuid')->willReturn($role);
        $this->mockRepo
            ->expects($this->once())
            ->method('update')
            ->willReturn($role);

        $this->mockAudit
            ->expects($this->once())
            ->method('log');

        $handler = new UpdateRoleHandler($this->mockRepo, $this->mockAudit);
        $dto = new UpdateRoleDTO(name: 'UPDATED');

        $result = $handler->handle(new UpdateRoleCommand($role->uuid, $dto));
        $this->assertInstanceOf(Role::class, $result);
    }

    public function test_delete_role_handler_throws_when_role_not_found(): void
    {
        $this->mockRepo->method('findByUuid')->willReturn(null);

        $handler = new DeleteRoleHandler($this->mockRepo, $this->mockAudit);

        $this->expectException(RoleNotFoundException::class);
        $handler->handle(new DeleteRoleCommand('non-existent-uuid'));
    }

    public function test_delete_role_handler_calls_delete(): void
    {
        $role = $this->makeDummyRole();

        $this->mockRepo->method('findByUuid')->willReturn($role);
        $this->mockRepo
            ->expects($this->once())
            ->method('delete');

        $this->mockAudit
            ->expects($this->once())
            ->method('log');

        $handler = new DeleteRoleHandler($this->mockRepo, $this->mockAudit);
        $handler->handle(new DeleteRoleCommand($role->uuid));
    }
}

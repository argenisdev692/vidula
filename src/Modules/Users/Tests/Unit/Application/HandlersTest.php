<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Unit\Application;

use Modules\Users\Application\Commands\CreateUser\CreateUserCommand;
use Modules\Users\Application\Commands\CreateUser\CreateUserHandler;
use Modules\Users\Application\Commands\DeleteUser\DeleteUserCommand;
use Modules\Users\Application\Commands\DeleteUser\DeleteUserHandler;
use Modules\Users\Application\Commands\UpdateUser\UpdateUserCommand;
use Modules\Users\Application\Commands\UpdateUser\UpdateUserHandler;
use Modules\Users\Application\DTOs\CreateUserDTO;
use Modules\Users\Application\DTOs\UpdateUserDTO;
use Modules\Users\Domain\Entities\User;
use Modules\Users\Domain\Enums\UserStatus;
use Modules\Users\Domain\Exceptions\UserNotFoundException;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Modules\Users\Domain\ValueObjects\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Audit\AuditInterface;

/**
 * Handlers Test — Application layer tests with mocked repository + audit.
 */
final class HandlersTest extends TestCase
{
    /** @var UserRepositoryPort&MockObject */
    private UserRepositoryPort $mockRepo;

    /** @var AuditInterface&MockObject */
    private AuditInterface $mockAudit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRepo = $this->createMock(UserRepositoryPort::class);
        $this->mockAudit = $this->createMock(AuditInterface::class);
    }

    private function makeDummyUser(string $uuid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee'): User
    {
        return new User(
            id: new UserId(1),
            uuid: $uuid,
            name: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            status: UserStatus::Active,
        );
    }

    // ── CreateUserHandler ──

    public function test_create_user_handler_calls_repository_create(): void
    {
        $dto = new CreateUserDTO(
            name: 'Alice',
            email: 'alice@example.com',
        );

        $this->mockRepo
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->makeDummyUser());

        $handler = new CreateUserHandler($this->mockRepo, $this->mockAudit);
        $result = $handler->handle(new CreateUserCommand($dto));

        $this->assertInstanceOf(User::class, $result);
    }

    // ── UpdateUserHandler ──

    public function test_update_user_handler_throws_when_user_not_found(): void
    {
        $this->mockRepo->method('findByUuid')->willReturn(null);

        $handler = new UpdateUserHandler($this->mockRepo, $this->mockAudit);
        $dto = new UpdateUserDTO(name: 'Updated');

        $this->expectException(UserNotFoundException::class);
        $handler->handle(new UpdateUserCommand('non-existent-uuid', $dto));
    }

    public function test_update_user_handler_calls_update_when_user_exists(): void
    {
        $user = $this->makeDummyUser();

        $this->mockRepo->method('findByUuid')->willReturn($user);
        $this->mockRepo
            ->expects($this->once())
            ->method('update')
            ->willReturn($user);

        $handler = new UpdateUserHandler($this->mockRepo, $this->mockAudit);
        $dto = new UpdateUserDTO(name: 'Updated Name');

        $result = $handler->handle(new UpdateUserCommand($user->uuid, $dto));
        $this->assertInstanceOf(User::class, $result);
    }

    // ── DeleteUserHandler ──

    public function test_delete_user_handler_throws_when_user_not_found(): void
    {
        $this->mockRepo->method('findByUuid')->willReturn(null);

        $handler = new DeleteUserHandler($this->mockRepo, $this->mockAudit);

        $this->expectException(UserNotFoundException::class);
        $handler->handle(new DeleteUserCommand('non-existent-uuid'));
    }

    public function test_delete_user_handler_calls_soft_delete(): void
    {
        $user = $this->makeDummyUser();

        $this->mockRepo->method('findByUuid')->willReturn($user);
        $this->mockRepo
            ->expects($this->once())
            ->method('softDelete');

        $handler = new DeleteUserHandler($this->mockRepo, $this->mockAudit);
        $handler->handle(new DeleteUserCommand($user->uuid));
    }
}

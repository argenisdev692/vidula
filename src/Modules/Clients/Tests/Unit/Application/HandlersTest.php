<?php

declare(strict_types=1);

namespace Modules\Clients\Tests\Unit\Application;

use Modules\Clients\Application\Commands\CreateClient\CreateClientCommand;
use Modules\Clients\Application\Commands\CreateClient\CreateClientHandler;
use Modules\Clients\Application\Commands\DeleteClient\DeleteClientCommand;
use Modules\Clients\Application\Commands\DeleteClient\DeleteClientHandler;
use Modules\Clients\Application\Commands\UpdateClient\UpdateClientCommand;
use Modules\Clients\Application\Commands\UpdateClient\UpdateClientHandler;
use Modules\Clients\Application\DTOs\CreateClientDTO;
use Modules\Clients\Application\DTOs\UpdateClientDTO;
use Modules\Clients\Domain\Entities\Client;
use Modules\Clients\Domain\Exceptions\ClientNotFoundException;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Clients\Domain\ValueObjects\ClientId;
use Modules\Clients\Domain\ValueObjects\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Audit\AuditInterface;

/**
 * HandlersTest — Application layer tests with mocked repository + audit.
 */
final class HandlersTest extends TestCase
{
    /** @var ClientRepositoryPort&MockObject */
    private ClientRepositoryPort $mockRepo;

    /** @var AuditInterface&MockObject */
    private AuditInterface $mockAudit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRepo = $this->createMock(ClientRepositoryPort::class);
        $this->mockAudit = $this->createMock(AuditInterface::class);
    }

    private function makeDummyClient(string $uuid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee'): Client
    {
        return new Client(
            id: new ClientId($uuid),
            userId: new UserId('11111111-2222-3333-4444-555555555555'),
            clientName: 'Test Client',
            email: 'test@example.com',
        );
    }

    // ── CreateClientHandler ──

    public function test_create_client_handler_calls_repository_save(): void
    {
        $dto = new CreateClientDTO(
            userUuid: '11111111-2222-3333-4444-555555555555',
            clientName: 'New Client',
            email: 'new@example.com',
        );

        $this->mockRepo
            ->expects($this->once())
            ->method('save');

        $this->mockAudit
            ->expects($this->once())
            ->method('log');

        $handler = new CreateClientHandler($this->mockRepo, $this->mockAudit);
        $uuid = $handler->handle(new CreateClientCommand($dto));

        $this->assertNotEmpty($uuid);
        $this->assertTrue(\Ramsey\Uuid\Uuid::isValid($uuid));
    }

    // ── UpdateClientHandler ──

    public function test_update_client_handler_throws_when_not_found(): void
    {
        $this->mockRepo->method('findById')->willReturn(null);

        $handler = new UpdateClientHandler($this->mockRepo, $this->mockAudit);
        $dto = new UpdateClientDTO(clientName: 'Updated');

        $this->expectException(ClientNotFoundException::class);
        $handler->handle(new UpdateClientCommand('non-existent-uuid', $dto));
    }

    public function test_update_client_handler_calls_save_when_found(): void
    {
        $client = $this->makeDummyClient();

        $this->mockRepo->method('findById')->willReturn($client);
        $this->mockRepo
            ->expects($this->once())
            ->method('save');

        $this->mockAudit
            ->expects($this->once())
            ->method('log');

        $handler = new UpdateClientHandler($this->mockRepo, $this->mockAudit);
        $dto = new UpdateClientDTO(clientName: 'Updated Name');

        $handler->handle(new UpdateClientCommand($client->id->value, $dto));
    }

    // ── DeleteClientHandler ──

    public function test_delete_client_handler_throws_when_not_found(): void
    {
        $this->mockRepo->method('findById')->willReturn(null);

        $handler = new DeleteClientHandler($this->mockRepo, $this->mockAudit);

        $this->expectException(ClientNotFoundException::class);
        $handler->handle(new DeleteClientCommand('non-existent-uuid'));
    }

    public function test_delete_client_handler_calls_delete(): void
    {
        $client = $this->makeDummyClient();

        $this->mockRepo->method('findById')->willReturn($client);
        $this->mockRepo
            ->expects($this->once())
            ->method('delete');

        $this->mockAudit
            ->expects($this->once())
            ->method('log');

        $handler = new DeleteClientHandler($this->mockRepo, $this->mockAudit);
        $handler->handle(new DeleteClientCommand($client->id->value));
    }
}

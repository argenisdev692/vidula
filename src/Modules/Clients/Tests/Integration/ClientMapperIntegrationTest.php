<?php

declare(strict_types=1);

namespace Modules\Clients\Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Clients\Domain\Entities\Client;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;
use Modules\Clients\Infrastructure\Persistence\Mappers\ClientMapper;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;
use Tests\TestCase;

/**
 * ClientMapperIntegrationTest — DB round-trip via Mapper.
 */
final class ClientMapperIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mapper_converts_eloquent_model_to_domain_entity(): void
    {
        $user = UserEloquentModel::factory()->create();

        /** @var ClientEloquentModel $model */
        $model = ClientEloquentModel::factory()->create([
            'user_id' => $user->id,
            'client_name' => 'Integration Test Client',
            'email' => 'integration@example.com',
        ]);

        // Reload with user relationship
        $model = ClientEloquentModel::with('user')->find($model->id);

        $entity = ClientMapper::toDomain($model);

        $this->assertInstanceOf(Client::class, $entity);
        $this->assertSame($model->uuid, $entity->id->value);
        $this->assertSame('Integration Test Client', $entity->clientName);
        $this->assertSame('integration@example.com', $entity->email);
        $this->assertNotNull($entity->createdAt);
    }

    public function test_mapper_handles_trashed_models(): void
    {
        $user = UserEloquentModel::factory()->create();

        /** @var ClientEloquentModel $model */
        $model = ClientEloquentModel::factory()->create([
            'user_id' => $user->id,
            'client_name' => 'Deleted Client',
        ]);
        $model->delete();

        $trashedModel = ClientEloquentModel::withTrashed()
            ->with('user')
            ->where('uuid', $model->uuid)
            ->first();

        $entity = ClientMapper::toDomain($trashedModel);

        $this->assertNotNull($entity->deletedAt);
    }

    public function test_repository_save_and_find_round_trip(): void
    {
        /** @var \Modules\Clients\Domain\Ports\ClientRepositoryPort $repo */
        $repo = app(\Modules\Clients\Domain\Ports\ClientRepositoryPort::class);
        $user = UserEloquentModel::factory()->create();

        $client = Client::create(
            id: new \Modules\Clients\Domain\ValueObjects\ClientId($uuid = fake()->uuid()),
            userId: new \Modules\Clients\Domain\ValueObjects\UserId($user->uuid),
            clientName: 'Round Trip Client',
            email: 'roundtrip@example.com',
        );

        $repo->save($client);

        $found = $repo->findById(new \Modules\Clients\Domain\ValueObjects\ClientId($uuid));
        $this->assertNotNull($found);
        $this->assertSame('Round Trip Client', $found->clientName);
    }

    public function test_repository_delete_and_restore(): void
    {
        /** @var \Modules\Clients\Domain\Ports\ClientRepositoryPort $repo */
        $repo = app(\Modules\Clients\Domain\Ports\ClientRepositoryPort::class);
        $user = UserEloquentModel::factory()->create();

        $client = Client::create(
            id: new \Modules\Clients\Domain\ValueObjects\ClientId($uuid = fake()->uuid()),
            userId: new \Modules\Clients\Domain\ValueObjects\UserId($user->uuid),
            clientName: 'Delete Test',
        );

        $repo->save($client);
        $repo->delete(new \Modules\Clients\Domain\ValueObjects\ClientId($uuid));

        // Should still find with withTrashed (our findById uses withTrashed)
        $found = $repo->findById(new \Modules\Clients\Domain\ValueObjects\ClientId($uuid));
        $this->assertNotNull($found);
        $this->assertNotNull($found->deletedAt);

        // Restore
        $repo->restore(new \Modules\Clients\Domain\ValueObjects\ClientId($uuid));
        $restored = $repo->findById(new \Modules\Clients\Domain\ValueObjects\ClientId($uuid));
        $this->assertNotNull($restored);
        $this->assertNull($restored->deletedAt);
    }
}

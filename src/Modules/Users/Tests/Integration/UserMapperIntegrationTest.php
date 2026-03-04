<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Users\Domain\Entities\User;
use Modules\Users\Domain\Enums\UserStatus;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;
use Modules\Users\Infrastructure\Persistence\Mappers\UserMapper;
use Tests\TestCase;

/**
 * UserMapperIntegrationTest — DB round-trip via Mapper.
 */
final class UserMapperIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mapper_converts_eloquent_model_to_domain_entity(): void
    {
        /** @var UserEloquentModel $model */
        $model = UserEloquentModel::query()->create([
            'uuid' => fake()->uuid(),
            'name' => 'Integration',
            'last_name' => 'Test',
            'email' => 'integration@example.com',
            'password' => bcrypt('secret'),
            'status' => 'active',
        ]);

        $entity = UserMapper::toDomain($model);

        $this->assertInstanceOf(User::class, $entity);
        $this->assertSame($model->uuid, $entity->uuid);
        $this->assertSame('Integration', $entity->name);
        $this->assertSame('Test', $entity->lastName);
        $this->assertSame('integration@example.com', $entity->email);
        $this->assertSame(UserStatus::Active, $entity->status);
        $this->assertNotNull($entity->createdAt);
    }

    public function test_mapper_sets_deleted_status_for_trashed_model(): void
    {
        /** @var UserEloquentModel $model */
        $model = UserEloquentModel::query()->create([
            'uuid' => fake()->uuid(),
            'name' => 'Deleted',
            'email' => 'deleted@example.com',
            'password' => bcrypt('secret'),
            'status' => 'active',
        ]);
        $model->delete();

        $trashedModel = UserEloquentModel::query()
            ->withTrashed()
            ->where('uuid', $model->uuid)
            ->first();

        $entity = UserMapper::toDomain($trashedModel);

        $this->assertSame(UserStatus::Deleted, $entity->status);
        $this->assertNotNull($entity->deletedAt);
    }

    public function test_repository_create_and_find_by_uuid_round_trip(): void
    {
        /** @var \Modules\Users\Domain\Ports\UserRepositoryPort $repo */
        $repo = app(\Modules\Users\Domain\Ports\UserRepositoryPort::class);

        $user = $repo->create([
            'uuid' => $uuid = fake()->uuid(),
            'name' => 'Round',
            'last_name' => 'Trip',
            'email' => 'roundtrip@example.com',
            'password' => bcrypt('secret'),
            'status' => 'active',
        ]);

        $this->assertSame($uuid, $user->uuid);

        $found = $repo->findByUuid($uuid);
        $this->assertNotNull($found);
        $this->assertSame('Round', $found->name);
    }

    public function test_repository_soft_delete_and_restore(): void
    {
        /** @var \Modules\Users\Domain\Ports\UserRepositoryPort $repo */
        $repo = app(\Modules\Users\Domain\Ports\UserRepositoryPort::class);

        $user = $repo->create([
            'uuid' => $uuid = fake()->uuid(),
            'name' => 'SoftDel',
            'email' => 'softdel@example.com',
            'password' => bcrypt('secret'),
            'status' => 'active',
        ]);

        $repo->softDelete($uuid);

        // Should not find by default (SoftDeletes)
        $this->assertNull($repo->findByUuid($uuid));

        // Restore
        $repo->restore($uuid);
        $restored = $repo->findByUuid($uuid);
        $this->assertNotNull($restored);
        $this->assertSame('SoftDel', $restored->name);
    }
}

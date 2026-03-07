<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel as User;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createProductWebUser(): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['VIEW_PRODUCTS', 'CREATE_PRODUCTS', 'UPDATE_PRODUCTS', 'DELETE_PRODUCTS', 'RESTORE_PRODUCTS'] as $permission) {
        PermissionEloquentModel::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ], [
            'uuid' => Uuid::uuid4()->toString(),
        ]);
    }

    $role = Role::firstOrCreate([
        'name' => 'PRODUCTS_TEST_ADMIN',
        'guard_name' => 'web',
    ], [
        'uuid' => Uuid::uuid4()->toString(),
    ]);

    $role->syncPermissions(PermissionEloquentModel::where('guard_name', 'web')->whereIn('name', [
        'VIEW_PRODUCTS',
        'CREATE_PRODUCTS',
        'UPDATE_PRODUCTS',
        'DELETE_PRODUCTS',
        'RESTORE_PRODUCTS',
    ])->get());

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('lists product data', function () {
    $user = createProductWebUser();
    ProductEloquentModel::factory()->count(3)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->getJson(route('product.data.index'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'userId', 'title', 'createdAt']
            ],
            'meta' => ['total', 'perPage']
        ]);
});

it('validates required fields on create', function () {
    $user = createProductWebUser();
    $this->actingAs($user)
        ->postJson(route('product.data.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'slug', 'price']);
});

it('shows product data', function () {
    $user = createProductWebUser();
    $uuid = (string) Str::uuid();
    $product = ProductEloquentModel::factory()->create([
        'uuid' => $uuid,
        'user_id' => $user->id,
        'title' => 'Test Product Title'
    ]);

    $this->actingAs($user)
        ->getJson(route('product.data.show', $uuid))
        ->assertOk()
        ->assertJsonPath('data.title', 'Test Product Title');
});

it('soft deletes product data', function () {
    $user = createProductWebUser();
    $uuid = (string) Str::uuid();
    $product = ProductEloquentModel::factory()->create([
        'uuid' => $uuid,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->deleteJson(route('product.data.destroy', $uuid))
        ->assertOk()
        ->assertJson(['message' => 'Product deleted successfully']);

    $this->assertDatabaseHas('products', [
        'uuid' => $uuid,
    ]);

    expect(ProductEloquentModel::withTrashed()->where('uuid', $uuid)->first()->deleted_at)->not->toBeNull();
});

it('restores soft deleted product data', function () {
    $user = createProductWebUser();
    $uuid = (string) Str::uuid();
    $product = ProductEloquentModel::factory()->create([
        'uuid' => $uuid,
        'user_id' => $user->id,
        'deleted_at' => now(),
    ]);

    $this->actingAs($user)
        ->patchJson(route('product.data.restore', $uuid))
        ->assertOk()
        ->assertJson(['message' => 'Product restored successfully']);

    expect(ProductEloquentModel::where('uuid', $uuid)->first()->deleted_at)->toBeNull();
});

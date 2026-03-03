<?php

declare(strict_types=1);

use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel as User;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('lists product data', function () {
    $user = User::factory()->create();
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
    $user = User::factory()->create();
    $this->actingAs($user)
        ->postJson(route('product.data.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'slug', 'price']);
});

it('shows product data', function () {
    $user = User::factory()->create();
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
    $user = User::factory()->create();
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
    $user = User::factory()->create();
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

<?php

declare(strict_types=1);

use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel as User;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Pest uses $this for assertions and requests
uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Generate a user to authenticate with during tests
});

it('lists client data', function () {
    $user = User::factory()->create();
    ClientEloquentModel::factory()->count(3)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->getJson(route('client.data.index'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['uuid', 'userUuid', 'clientName', 'createdAt']
            ],
            'meta' => ['total', 'perPage']
        ]);
});

it('creates client data', function () {
    $user = User::factory()->create();
    $payload = [
        'userUuid' => $user->uuid,
        'clientName' => 'Acme Corp',
        'email' => 'contact@acme.com',
        'phone' => '1234567890'
    ];

    $this->actingAs($user)
        ->postJson(route('client.data.store'), $payload)
        ->assertCreated()
        ->assertJsonStructure(['message', 'uuid']);

    $this->assertDatabaseHas('clients', [
        'user_id' => $user->id,
        'client_name' => 'Acme Corp'
    ]);
});

it('validates required fields on create', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->postJson(route('client.data.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['userUuid', 'clientName']);
});

it('shows client data', function () {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();
    $client = ClientEloquentModel::factory()->create([
        'uuid' => $uuid,
        'user_id' => $user->id,
        'client_name' => 'Show Test Corp'
    ]);

    $this->actingAs($user)
        ->getJson(route('client.data.show', $uuid))
        ->assertOk()
        ->assertJsonPath('data.clientName', 'Show Test Corp');
});

it('updates client data', function () {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();
    $client = ClientEloquentModel::factory()->create([
        'uuid' => $uuid,
        'user_id' => $user->id,
        'client_name' => 'Old Name'
    ]);

    $this->actingAs($user)
        ->putJson(route('client.data.update', $uuid), [
            'clientName' => 'New Name'
        ])
        ->assertOk()
        ->assertJson(['message' => 'Client updated successfully']);

    $this->assertDatabaseHas('clients', [
        'uuid' => $uuid,
        'client_name' => 'New Name'
    ]);
});

it('soft deletes client data', function () {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();
    $client = ClientEloquentModel::factory()->create([
        'uuid' => $uuid,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->deleteJson(route('client.data.destroy', $uuid))
        ->assertOk()
        ->assertJson(['message' => 'Client deleted successfully']);

    $this->assertDatabaseHas('clients', [
        'uuid' => $uuid,
    ]);

    expect(ClientEloquentModel::withTrashed()->where('uuid', $uuid)->first()->deleted_at)->not->toBeNull();
});

it('restores soft deleted client data', function () {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();
    $client = ClientEloquentModel::factory()->create([
        'uuid' => $uuid,
        'user_id' => $user->id,
        'deleted_at' => now(),
    ]);

    $this->actingAs($user)
        ->patchJson(route('client.data.restore', $uuid))
        ->assertOk()
        ->assertJson(['message' => 'Client restored successfully']);

    expect(ClientEloquentModel::where('uuid', $uuid)->first()->deleted_at)->toBeNull();
});

it('exports client data to excel', function () {
    $user = User::factory()->create();
    ClientEloquentModel::factory()->count(3)->create();

    $this->actingAs($user)
        ->getJson(route('client.data.export', ['format' => 'excel']))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('exports client data to pdf', function () {
    $user = User::factory()->create();
    ClientEloquentModel::factory()->count(3)->create();

    $this->actingAs($user)
        ->getJson(route('client.data.export', ['format' => 'pdf']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

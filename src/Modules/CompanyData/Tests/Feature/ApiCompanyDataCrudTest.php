<?php

declare(strict_types=1);

use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel as User;
use Modules\CompanyData\Infrastructure\Persistence\Eloquent\Models\CompanyDataEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Pest uses $this for assertions and requests
uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Generate a user to authenticate with during tests
});

it('lists company data', function () {
    $user = User::factory()->create();
    CompanyDataEloquentModel::factory()->count(3)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->getJson(route('api.admin.company_data.index'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'userId', 'companyName', 'createdAt']
            ],
            'meta' => ['total', 'perPage']
        ]);
});

it('creates company data', function () {
    $user = User::factory()->create();
    $payload = [
        'user_id' => $user->id,
        'company_name' => 'Acme Corp',
        'email' => 'contact@acme.com',
        'phone' => '1234567890'
    ];

    $this->actingAs($user)
        ->postJson(route('api.admin.company_data.store'), $payload)
        ->assertCreated()
        ->assertJsonStructure(['message']);

    $this->assertDatabaseHas('company_data', [
        'user_id' => $user->id,
        'company_name' => 'Acme Corp'
    ]);
});

it('validates required fields on create', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->postJson(route('api.admin.company_data.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id', 'company_name']);
});

it('shows company data', function () {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();
    $companyData = CompanyDataEloquentModel::factory()->create([
        'uuid' => $uuid,
        'user_id' => $user->id,
        'company_name' => 'Show Test Corp'
    ]);

    $this->actingAs($user)
        ->getJson(route('api.admin.company_data.show', $uuid))
        ->assertOk()
        ->assertJsonPath('data.companyName', 'Show Test Corp');
});

it('updates company data', function () {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();
    $companyData = CompanyDataEloquentModel::factory()->create([
        'uuid' => $uuid,
        'user_id' => $user->id,
        'company_name' => 'Old Name'
    ]);

    $this->actingAs($user)
        ->putJson(route('api.admin.company_data.update', $uuid), [
            'company_name' => 'New Name'
        ])
        ->assertOk()
        ->assertJson(['message' => 'Company data updated successfully']);

    $this->assertDatabaseHas('company_data', [
        'uuid' => $uuid,
        'company_name' => 'New Name'
    ]);
});

it('soft deletes company data', function () {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();
    $companyData = CompanyDataEloquentModel::factory()->create([
        'uuid' => $uuid,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->deleteJson(route('api.admin.company_data.destroy', $uuid))
        ->assertOk()
        ->assertJson(['message' => 'Company data deleted successfully']);

    $this->assertDatabaseHas('company_data', [
        'uuid' => $uuid,
    ]);

    expect(CompanyDataEloquentModel::withTrashed()->where('uuid', $uuid)->first()->deleted_at)->not->toBeNull();
});

it('restores soft deleted company data', function () {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();
    $companyData = CompanyDataEloquentModel::factory()->create([
        'uuid' => $uuid,
        'user_id' => $user->id,
        'deleted_at' => now(),
    ]);

    $this->actingAs($user)
        ->patchJson(route('api.admin.company_data.restore', $uuid))
        ->assertOk()
        ->assertJson(['message' => 'Company data restored successfully']);

    expect(CompanyDataEloquentModel::where('uuid', $uuid)->first()->deleted_at)->toBeNull();
});

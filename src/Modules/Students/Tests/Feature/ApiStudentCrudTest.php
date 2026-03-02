<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Student as StudentEloquentModel;
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
    StudentEloquentModel::factory()->count(3)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->getJson(route('student.index'))
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
        ->postJson(route('student.store'), $payload)
        ->assertCreated()
        ->assertJsonStructure(['message', 'uuid']);

    $this->assertDatabaseHas('student', [
        'user_id' => $user->id,
        'company_name' => 'Acme Corp'
    ]);
});

it('validates required fields on create', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->postJson(route('student.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id', 'company_name']);
});

it('shows company data', function () {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();
    $student = StudentEloquentModel::factory()->create([
        'uuid' => $uuid,
        'user_id' => $user->id,
        'company_name' => 'Show Test Corp'
    ]);

    $this->actingAs($user)
        ->getJson(route('student.show', $uuid))
        ->assertOk()
        ->assertJsonPath('data.companyName', 'Show Test Corp');
});

it('updates company data', function () {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();
    $student = StudentEloquentModel::factory()->create([
        'uuid' => $uuid,
        'user_id' => $user->id,
        'company_name' => 'Old Name'
    ]);

    $this->actingAs($user)
        ->putJson(route('student.update', $uuid), [
            'company_name' => 'New Name'
        ])
        ->assertOk()
        ->assertJson(['message' => 'Company data updated successfully']);

    $this->assertDatabaseHas('student', [
        'uuid' => $uuid,
        'company_name' => 'New Name'
    ]);
});

it('soft deletes company data', function () {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();
    $student = StudentEloquentModel::factory()->create([
        'uuid' => $uuid,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->deleteJson(route('student.destroy', $uuid))
        ->assertOk()
        ->assertJson(['message' => 'Company data deleted successfully']);

    $this->assertDatabaseHas('student', [
        'uuid' => $uuid,
    ]);

    expect(StudentEloquentModel::withTrashed()->where('uuid', $uuid)->first()->deleted_at)->not->toBeNull();
});

it('restores soft deleted company data', function () {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();
    $student = StudentEloquentModel::factory()->create([
        'uuid' => $uuid,
        'user_id' => $user->id,
        'deleted_at' => now(),
    ]);

    $this->actingAs($user)
        ->patchJson(route('student.restore', $uuid))
        ->assertOk()
        ->assertJson(['message' => 'Company data restored successfully']);

    expect(StudentEloquentModel::where('uuid', $uuid)->first()->deleted_at)->toBeNull();
});

it('exports company data to excel', function () {
    $user = User::factory()->create();
    StudentEloquentModel::factory()->count(3)->create();

    $this->actingAs($user)
        ->getJson(route('student.export', ['format' => 'excel']))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('exports company data to pdf', function () {
    $user = User::factory()->create();
    StudentEloquentModel::factory()->count(3)->create();

    $this->actingAs($user)
        ->getJson(route('student.export', ['format' => 'pdf']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

<?php

declare(strict_types=1);

use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel as User;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Pest uses $this for assertions and requests
uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Generate a user to authenticate with during tests
});

it('lists student data', function () {
    $user = User::factory()->create();
    StudentEloquentModel::factory()->count(3)->create();

    $this->actingAs($user)
        ->getJson(route('api.admin.student.index'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'createdAt']
            ],
            'meta' => ['total', 'perPage']
        ]);
});

it('creates student data', function () {
    $user = User::factory()->create();
    $payload = [
        'name' => 'John Doe',
        'email' => 'john@acme.com',
        'phone' => '1234567890'
    ];

    $this->actingAs($user)
        ->postJson(route('api.admin.student.store'), $payload)
        ->assertCreated()
        ->assertJsonStructure(['message']);

    $this->assertDatabaseHas('students', [
        'name' => 'John Doe'
    ]);
});

it('validates required fields on create', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->postJson(route('api.admin.student.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('shows student data', function () {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();
    $student = StudentEloquentModel::factory()->create([
        'uuid' => $uuid,
        'name' => 'Show Test Corp'
    ]);

    $this->actingAs($user)
        ->getJson(route('api.admin.student.show', $uuid))
        ->assertOk()
        ->assertJsonPath('data.name', 'Show Test Corp');
});

it('updates student data', function () {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();
    $student = StudentEloquentModel::factory()->create([
        'uuid' => $uuid,
        'name' => 'Old Name'
    ]);

    $this->actingAs($user)
        ->putJson(route('api.admin.student.update', $uuid), [
            'name' => 'New Name'
        ])
        ->assertOk()
        ->assertJson(['message' => 'Student updated successfully']);

    $this->assertDatabaseHas('students', [
        'uuid' => $uuid,
        'name' => 'New Name'
    ]);
});

it('soft deletes student data', function () {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();
    $student = StudentEloquentModel::factory()->create([
        'uuid' => $uuid,
    ]);

    $this->actingAs($user)
        ->deleteJson(route('api.admin.student.destroy', $uuid))
        ->assertOk()
        ->assertJson(['message' => 'Student deleted successfully']);

    $this->assertDatabaseHas('students', [
        'uuid' => $uuid,
    ]);

    expect(StudentEloquentModel::withTrashed()->where('uuid', $uuid)->first()->deleted_at)->not->toBeNull();
});

it('restores soft deleted student data', function () {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();
    $student = StudentEloquentModel::factory()->create([
        'uuid' => $uuid,
        'deleted_at' => now(),
    ]);

    $this->actingAs($user)
        ->patchJson(route('api.admin.student.restore', $uuid))
        ->assertOk()
        ->assertJson(['message' => 'Student restored successfully']);

    expect(StudentEloquentModel::where('uuid', $uuid)->first()->deleted_at)->toBeNull();
});

it('exports student data to excel', function () {
    $user = User::factory()->create();
    StudentEloquentModel::factory()->count(3)->create();

    $this->actingAs($user)
        ->getJson(route('api.admin.student.export', ['format' => 'excel']))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('exports student data to pdf', function () {
    $user = User::factory()->create();
    StudentEloquentModel::factory()->count(3)->create();

    $this->actingAs($user)
        ->getJson(route('api.admin.student.export', ['format' => 'pdf']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

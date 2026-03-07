<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel as User;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

// Pest uses $this for assertions and requests
uses(TestCase::class, RefreshDatabase::class);

function createStudentApiUser(): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['VIEW_STUDENTS', 'CREATE_STUDENTS', 'UPDATE_STUDENTS', 'DELETE_STUDENTS'] as $permission) {
        PermissionEloquentModel::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'sanctum',
        ], [
            'uuid' => Uuid::uuid4()->toString(),
        ]);
    }

    $role = Role::firstOrCreate([
        'name' => 'STUDENTS_TEST_ADMIN',
        'guard_name' => 'sanctum',
    ], [
        'uuid' => Uuid::uuid4()->toString(),
    ]);

    $role->syncPermissions(PermissionEloquentModel::where('guard_name', 'sanctum')->whereIn('name', [
        'VIEW_STUDENTS',
        'CREATE_STUDENTS',
        'UPDATE_STUDENTS',
        'DELETE_STUDENTS',
    ])->get());

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('lists student data', function () {
    $user = createStudentApiUser();
    StudentEloquentModel::factory()->count(3)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson(route('api.admin.student.index'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['uuid', 'name', 'createdAt']
            ],
            'meta' => ['total', 'perPage']
        ]);
});

it('creates student data', function () {
    $user = createStudentApiUser();
    $payload = [
        'name' => 'John Doe',
        'email' => 'john@acme.com',
        'phone' => '1234567890'
    ];

    $this->actingAs($user, 'sanctum')
        ->postJson(route('api.admin.student.store'), $payload)
        ->assertCreated()
        ->assertJsonStructure(['message']);

    $this->assertDatabaseHas('students', [
        'name' => 'John Doe'
    ]);
});

it('validates required fields on create', function () {
    $user = createStudentApiUser();
    $this->actingAs($user, 'sanctum')
        ->postJson(route('api.admin.student.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('shows student data', function () {
    $user = createStudentApiUser();
    $uuid = (string) Str::uuid();
    StudentEloquentModel::factory()->create([
        'uuid' => $uuid,
        'name' => 'Show Test Corp'
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson(route('api.admin.student.show', $uuid))
        ->assertOk()
        ->assertJsonPath('data.name', 'Show Test Corp');
});

it('updates student data', function () {
    $user = createStudentApiUser();
    $uuid = (string) Str::uuid();
    StudentEloquentModel::factory()->create([
        'uuid' => $uuid,
        'name' => 'Old Name'
    ]);

    $this->actingAs($user, 'sanctum')
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
    $user = createStudentApiUser();
    $uuid = (string) Str::uuid();
    StudentEloquentModel::factory()->create([
        'uuid' => $uuid,
    ]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson(route('api.admin.student.destroy', $uuid))
        ->assertOk()
        ->assertJson(['message' => 'Student deleted successfully']);

    $this->assertDatabaseHas('students', [
        'uuid' => $uuid,
    ]);

    expect(StudentEloquentModel::withTrashed()->where('uuid', $uuid)->first()->deleted_at)->not->toBeNull();
});

it('restores soft deleted student data', function () {
    $user = createStudentApiUser();
    $uuid = (string) Str::uuid();
    StudentEloquentModel::factory()->create([
        'uuid' => $uuid,
        'deleted_at' => now(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->patchJson(route('api.admin.student.restore', $uuid))
        ->assertOk()
        ->assertJson(['message' => 'Student restored successfully']);

    expect(StudentEloquentModel::where('uuid', $uuid)->first()->deleted_at)->toBeNull();
});

it('exports student data to excel', function () {
    $user = createStudentApiUser();
    StudentEloquentModel::factory()->count(3)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson(route('api.admin.student.export', ['format' => 'excel']))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('exports student data to pdf', function () {
    $user = createStudentApiUser();
    StudentEloquentModel::factory()->count(3)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson(route('api.admin.student.export', ['format' => 'pdf']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

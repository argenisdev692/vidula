<?php

declare(strict_types=1);

namespace Modules\Students\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;
use Tests\TestCase;

final class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        return $admin;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => 'Ada Lovelace',
            'email' => 'ada@academy.test',
            'phone' => '+15551234567',
            'dni' => null,
            'address' => '1 Analytical Engine Way',
            'avatar' => null,
            'notes' => null,
            'status' => 'DRAFT',
            'active' => true,
            ...$overrides,
        ];
    }

    public function test_super_admin_creates_a_global_student(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/students', $this->validPayload())
            ->assertRedirect();

        $student = StudentEloquentModel::query()->where('email', 'ada@academy.test')->firstOrFail();

        $this->assertSame('Ada Lovelace', $student->name);
        $this->assertSame('DRAFT', $student->status);
        $this->assertTrue($student->active);
        $this->assertSame('+15551234567', $student->phone);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        StudentEloquentModel::factory()->create(['email' => 'taken@academy.test']);

        $this->actingAs($this->superAdmin())
            ->post('/students', $this->validPayload(['email' => 'taken@academy.test']))
            ->assertSessionHasErrors('email');
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/students', $this->validPayload(['phone' => '555 000 0000']))
            ->assertSessionHasErrors('phone');
    }

    public function test_update_changes_name_and_lifecycle_status(): void
    {
        $admin = $this->superAdmin();
        $student = StudentEloquentModel::factory()->create(['name' => 'Old Name', 'status' => 'DRAFT']);

        $this->actingAs($admin)->put("/students/{$student->uuid}", $this->validPayload([
            'name' => 'New Name',
            'status' => 'ACTIVE',
            'email' => $student->email,
        ]))->assertRedirect();

        $student->refresh();
        $this->assertSame('New Name', $student->name);
        $this->assertSame('ACTIVE', $student->status);
    }

    public function test_delete_then_restore_a_student(): void
    {
        $admin = $this->superAdmin();
        $student = StudentEloquentModel::factory()->create();

        $this->actingAs($admin)->delete("/students/{$student->uuid}")->assertRedirect();
        $this->assertSoftDeleted('students', ['uuid' => $student->uuid]);

        $this->actingAs($admin)->post("/students/{$student->uuid}/restore")->assertRedirect();
        $this->assertDatabaseHas('students', ['uuid' => $student->uuid, 'deleted_at' => null]);
    }

    public function test_bulk_delete_then_restore(): void
    {
        $admin = $this->superAdmin();
        $uuids = StudentEloquentModel::factory()->count(3)->create()->pluck('uuid')->all();

        $this->actingAs($admin)->post('/students/bulk-delete', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertSoftDeleted('students', ['uuid' => $uuid]);
        }

        $this->actingAs($admin)->post('/students/bulk-restore', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertDatabaseHas('students', ['uuid' => $uuid, 'deleted_at' => null]);
        }
    }

    public function test_bulk_delete_rejects_more_than_500_uuids(): void
    {
        $uuids = array_map(static fn (): string => (string) Str::uuid(), range(1, 501));

        $this->actingAs($this->superAdmin())
            ->postJson('/students/bulk-delete', ['uuids' => $uuids])
            ->assertStatus(422)
            ->assertJsonValidationErrors('uuids');
    }

    public function test_search_filter_narrows_the_list(): void
    {
        StudentEloquentModel::factory()->create(['name' => 'Northern Lights']);
        StudentEloquentModel::factory()->create(['name' => 'Southern Gardens']);

        $this->actingAs($this->superAdmin())
            ->getJson('/students?search=Northern')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Northern Lights'])
            ->assertJsonMissing(['name' => 'Southern Gardens']);
    }

    public function test_user_role_cannot_create_students(): void
    {
        $user = User::factory()->create();
        $user->assignRole('USER');

        $this->actingAs($user)
            ->post('/students', $this->validPayload())
            ->assertForbidden();
    }
}

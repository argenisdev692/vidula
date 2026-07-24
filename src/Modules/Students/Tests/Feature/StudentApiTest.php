<?php

declare(strict_types=1);

namespace Modules\Students\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;
use Tests\TestCase;

final class StudentApiTest extends TestCase
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

    public function test_sanctum_lists_students(): void
    {
        Sanctum::actingAs($this->superAdmin());
        StudentEloquentModel::factory()->create(['name' => 'API Student']);

        $this->getJson('/api/students')
            ->assertOk()
            ->assertJsonFragment(['name' => 'API Student']);
    }

    public function test_sanctum_shows_a_student_by_uuid(): void
    {
        Sanctum::actingAs($this->superAdmin());
        $student = StudentEloquentModel::factory()->create(['name' => 'Shown Student']);

        $this->getJson("/api/students/{$student->uuid}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Shown Student');
    }

    public function test_unauthenticated_api_is_rejected(): void
    {
        $this->getJson('/api/students')->assertUnauthorized();
    }
}

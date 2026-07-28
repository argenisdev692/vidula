<?php

declare(strict_types=1);

namespace Modules\Enrollments\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Enrollments\Domain\Enums\AttendanceStatus;
use Modules\Enrollments\Infrastructure\Persistence\Eloquent\Models\ClassroomAttendanceEloquentModel;
use Modules\Enrollments\Infrastructure\Persistence\Eloquent\Models\ClassroomEnrollmentEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ClassroomEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductSessionEloquentModel;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;
use Tests\TestCase;

final class EnrollmentManagementTest extends TestCase
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
     * @return array{student: StudentEloquentModel, classroom: ClassroomEloquentModel, session: ProductSessionEloquentModel}
     */
    private function classroomFixture(User $admin): array
    {
        $product = ProductEloquentModel::factory()->classroom()->create([
            'user_id' => $admin->id,
            'title' => 'Copilot Classroom',
        ]);
        $classroom = ClassroomEloquentModel::factory()->create([
            'product_id' => $product->id,
        ]);
        $session = ProductSessionEloquentModel::factory()->create([
            'product_id' => $product->id,
            'session_number' => 1,
            'title' => 'Session 1',
            'session_date' => now()->toDateString(),
        ]);
        $student = StudentEloquentModel::factory()->active()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@academy.test',
        ]);

        return compact('student', 'classroom', 'session');
    }

    public function test_super_admin_creates_enrollment(): void
    {
        $admin = $this->superAdmin();
        ['student' => $student, 'classroom' => $classroom] = $this->classroomFixture($admin);

        $this->actingAs($admin)
            ->post('/enrollments', [
                'student_uuid' => $student->uuid,
                'classroom_uuid' => $classroom->uuid,
                'enrolled_at' => now()->toDateString(),
                'enrollment_status' => 'active',
                'final_grade' => null,
                'notes' => null,
            ])
            ->assertRedirect();

        $enrollment = ClassroomEnrollmentEloquentModel::query()->firstOrFail();
        $this->assertSame($student->id, $enrollment->student_id);
        $this->assertSame($classroom->id, $enrollment->classroom_id);
    }

    public function test_duplicate_student_classroom_is_rejected(): void
    {
        $admin = $this->superAdmin();
        ['student' => $student, 'classroom' => $classroom] = $this->classroomFixture($admin);

        ClassroomEnrollmentEloquentModel::factory()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
        ]);

        $this->actingAs($admin)
            ->post('/enrollments', [
                'student_uuid' => $student->uuid,
                'classroom_uuid' => $classroom->uuid,
                'enrolled_at' => now()->toDateString(),
                'enrollment_status' => 'active',
            ])
            ->assertSessionHasErrors('student_uuid');
    }

    public function test_super_admin_updates_enrollment(): void
    {
        $admin = $this->superAdmin();
        ['student' => $student, 'classroom' => $classroom] = $this->classroomFixture($admin);
        $enrollment = ClassroomEnrollmentEloquentModel::factory()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'enrollment_status' => 'active',
        ]);

        $this->actingAs($admin)
            ->put("/enrollments/{$enrollment->uuid}", [
                'student_uuid' => $student->uuid,
                'classroom_uuid' => $classroom->uuid,
                'enrolled_at' => now()->toDateString(),
                'enrollment_status' => 'completed',
                'final_grade' => 95.5,
                'notes' => 'Excellent',
            ])
            ->assertRedirect();

        $enrollment->refresh();
        $this->assertSame('completed', $enrollment->enrollment_status->value);
        $this->assertSame('95.50', (string) $enrollment->final_grade);
    }

    public function test_super_admin_soft_deletes_and_restores_enrollment(): void
    {
        $admin = $this->superAdmin();
        ['student' => $student, 'classroom' => $classroom] = $this->classroomFixture($admin);
        $enrollment = ClassroomEnrollmentEloquentModel::factory()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
        ]);

        $this->actingAs($admin)
            ->delete("/enrollments/{$enrollment->uuid}")
            ->assertRedirect();

        $this->assertSoftDeleted('classroom_enrollments', ['uuid' => $enrollment->uuid]);

        $this->actingAs($admin)
            ->post("/enrollments/{$enrollment->uuid}/restore")
            ->assertRedirect();

        $this->assertDatabaseHas('classroom_enrollments', [
            'uuid' => $enrollment->uuid,
            'deleted_at' => null,
        ]);
    }

    public function test_bulk_delete_and_bulk_restore_enrollments(): void
    {
        $admin = $this->superAdmin();
        ['student' => $student, 'classroom' => $classroom] = $this->classroomFixture($admin);
        $first = ClassroomEnrollmentEloquentModel::factory()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
        ]);
        $secondStudent = StudentEloquentModel::factory()->active()->create();
        $second = ClassroomEnrollmentEloquentModel::factory()->create([
            'student_id' => $secondStudent->id,
            'classroom_id' => $classroom->id,
        ]);

        $this->actingAs($admin)
            ->post('/enrollments/bulk-delete', [
                'uuids' => [$first->uuid, $second->uuid],
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('classroom_enrollments', ['uuid' => $first->uuid]);
        $this->assertSoftDeleted('classroom_enrollments', ['uuid' => $second->uuid]);

        $this->actingAs($admin)
            ->post('/enrollments/bulk-restore', [
                'uuids' => [$first->uuid, $second->uuid],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('classroom_enrollments', [
            'uuid' => $first->uuid,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('classroom_enrollments', [
            'uuid' => $second->uuid,
            'deleted_at' => null,
        ]);
    }

    public function test_sync_attendance_marks_for_classroom(): void
    {
        $admin = $this->superAdmin();
        ['student' => $student, 'classroom' => $classroom, 'session' => $session] = $this->classroomFixture($admin);

        $enrollment = ClassroomEnrollmentEloquentModel::factory()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
        ]);

        $this->actingAs($admin)
            ->put("/enrollments/attendance/{$classroom->uuid}", [
                'marks' => [
                    [
                        'enrollment_uuid' => $enrollment->uuid,
                        'product_session_uuid' => $session->uuid,
                        'attendance_status' => 'present',
                        'observation' => null,
                        'date' => $session->session_date?->toDateString(),
                    ],
                ],
            ])
            ->assertRedirect();

        $mark = ClassroomAttendanceEloquentModel::query()->firstOrFail();
        $this->assertSame($enrollment->id, $mark->enrollment_id);
        $this->assertSame($session->id, $mark->product_session_id);
        $this->assertSame(AttendanceStatus::Present, $mark->attendance_status);
    }

    public function test_attendance_export_csv_xlsx_and_pdf_return_ok(): void
    {
        $admin = $this->superAdmin();
        ['classroom' => $classroom] = $this->classroomFixture($admin);

        foreach (['csv', 'xlsx', 'pdf'] as $format) {
            $this->actingAs($admin)
                ->get("/enrollments/attendance/{$classroom->uuid}/export?format={$format}")
                ->assertOk();
        }
    }

    public function test_api_lists_and_shows_enrollment(): void
    {
        $admin = $this->superAdmin();
        ['student' => $student, 'classroom' => $classroom] = $this->classroomFixture($admin);
        $enrollment = ClassroomEnrollmentEloquentModel::factory()->create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/enrollments')
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $enrollment->uuid);

        $this->getJson("/api/enrollments/{$enrollment->uuid}")
            ->assertOk()
            ->assertJsonPath('data.uuid', $enrollment->uuid);

        $this->getJson("/api/enrollments/attendance/{$classroom->uuid}")
            ->assertOk()
            ->assertJsonStructure(['classroom', 'sessions', 'enrollments', 'marks']);
    }

    public function test_user_role_cannot_create_enrollments(): void
    {
        $user = User::factory()->create();
        $user->assignRole('USER');
        $admin = $this->superAdmin();
        ['student' => $student, 'classroom' => $classroom] = $this->classroomFixture($admin);

        $this->actingAs($user)
            ->post('/enrollments', [
                'student_uuid' => $student->uuid,
                'classroom_uuid' => $classroom->uuid,
                'enrolled_at' => now()->toDateString(),
                'enrollment_status' => 'active',
            ])
            ->assertForbidden();
    }
}

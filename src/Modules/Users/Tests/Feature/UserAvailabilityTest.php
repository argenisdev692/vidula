<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserAvailabilityTest extends TestCase
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

    public function test_guests_cannot_check_user_availability(): void
    {
        $this->getJson('/users/availability?field=email&value=free@example.com')
            ->assertUnauthorized();
    }

    public function test_users_without_manage_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/users/availability?field=email&value=free@example.com')
            ->assertForbidden();
    }

    public function test_a_free_email_reports_available(): void
    {
        $this->actingAs($this->superAdmin())
            ->getJson('/users/availability?field=email&value=free@example.com')
            ->assertOk()
            ->assertExactJson(['available' => true]);
    }

    public function test_a_taken_email_reports_unavailable(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($this->superAdmin())
            ->getJson('/users/availability?field=email&value=taken@example.com')
            ->assertOk()
            ->assertExactJson(['available' => false]);
    }

    public function test_a_taken_username_reports_unavailable(): void
    {
        User::factory()->create(['username' => 'adalovelace123']);

        $this->actingAs($this->superAdmin())
            ->getJson('/users/availability?field=username&value=adalovelace123')
            ->assertOk()
            ->assertExactJson(['available' => false]);
    }

    public function test_the_ignored_user_reads_as_available(): void
    {
        $editing = User::factory()->create(['email' => 'self@example.com']);

        $this->actingAs($this->superAdmin())
            ->getJson("/users/availability?field=email&value=self@example.com&ignore={$editing->uuid}")
            ->assertOk()
            ->assertExactJson(['available' => true]);
    }

    public function test_an_unknown_field_is_rejected(): void
    {
        $this->actingAs($this->superAdmin())
            ->getJson('/users/availability?field=password&value=secret')
            ->assertStatus(422);
    }
}

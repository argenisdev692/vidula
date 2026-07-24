<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MonitoringDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_horizon_to_login(): void
    {
        $this->get('/horizon')->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_telescope_to_login(): void
    {
        $this->get('/telescope')->assertRedirect('/login');
    }

    public function test_authenticated_user_without_permission_cannot_access_horizon(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();

        $this->actingAs($user)->get('/horizon')->assertForbidden();
    }

    public function test_authenticated_user_without_permission_cannot_access_telescope(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();

        $this->actingAs($user)->get('/telescope')->assertForbidden();
    }

    public function test_super_admin_can_access_horizon_and_telescope(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        $this->actingAs($admin)->get('/horizon')->assertOk();
        $this->actingAs($admin)->get('/telescope')->assertOk();
    }
}

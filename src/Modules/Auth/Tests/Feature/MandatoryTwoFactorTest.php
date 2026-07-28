<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MandatoryTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        // phpunit.xml disables mandatory 2FA so other suites stay green; this
        // feature turns it back on to assert real enforcement (prompt §3).
        config()->set('security.two_factor.mandatory', true);
    }

    public function test_admin_without_two_factor_is_redirected_to_setup(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertRedirect(route('two-factor.setup'));
    }

    public function test_admin_can_open_the_two_factor_setup_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');

        $this->actingAs($admin)
            ->get(route('two-factor.setup'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/TwoFactorSetup'));
    }

    public function test_regular_user_without_two_factor_is_allowed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('USER');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_admin_without_two_factor_gets_403_on_json(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');

        $this->actingAs($admin)
            ->getJson('/dashboard')
            ->assertStatus(403)
            ->assertJsonPath('code', 'two_factor_required');
    }

    public function test_admin_with_confirmed_two_factor_can_access_dashboard(): void
    {
        $admin = User::factory()->withTwoFactor()->create();
        $admin->assignRole('ADMIN');

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk();
    }
}

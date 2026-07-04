<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class MandatoryTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        Route::middleware(['web', 'auth', 'two-factor.enforce'])
            ->get('/__protected', fn (): string => 'ok')
            ->name('two-factor-test.protected');
    }

    public function test_admin_without_two_factor_is_redirected_to_setup(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');

        $this->actingAs($admin)
            ->get('/__protected')
            ->assertRedirect(route('two-factor.setup'));
    }

    public function test_regular_user_without_two_factor_is_allowed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('USER');

        $this->actingAs($user)
            ->get('/__protected')
            ->assertOk()
            ->assertSee('ok');
    }

    public function test_admin_without_two_factor_gets_403_on_json(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');

        $this->actingAs($admin)
            ->getJson('/__protected')
            ->assertStatus(403);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class PasswordExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('security.password.expiry_months', 3);

        Route::middleware(['web', 'auth'])
            ->get('/__pw-protected', fn (): string => 'ok')
            ->name('password-expiry-test.protected');
    }

    public function test_user_with_expired_password_is_redirected_to_the_update_form(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()->subMonths(4)]);

        $this->actingAs($user)
            ->get('/__pw-protected')
            ->assertRedirect(route('password.expired'));
    }

    public function test_user_with_a_recent_password_is_allowed_through(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()->subDays(10)]);

        $this->actingAs($user)
            ->get('/__pw-protected')
            ->assertOk()
            ->assertSee('ok');
    }
}

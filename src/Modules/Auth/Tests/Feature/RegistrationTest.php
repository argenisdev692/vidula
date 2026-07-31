<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_registration_rejects_a_weak_password(): void
    {
        $this->postJson('/register', [
            'first_name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
            'terms_and_conditions' => true,
        ])->assertStatus(422)->assertJsonValidationErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
    }

    public function test_registration_creates_an_unverified_user_with_the_default_role(): void
    {
        $this->postJson('/register', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => 'Sup3rS3cret!2026',
            'password_confirmation' => 'Sup3rS3cret!2026',
            'terms_and_conditions' => true,
        ])->assertRedirect();

        $user = User::query()->where('email', 'jane@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('USER'));
        $this->assertNull($user->email_verified_at, 'Email must require verification.');
    }

    public function test_registration_is_throttled_to_three_attempts_per_ip_per_hour(): void
    {
        // Fortify's `guest` middleware redirects authenticated users (302) before
        // CreateNewUser's limiter runs — disable it so all four POSTs hit the action.
        $this->withoutMiddleware(RedirectIfAuthenticated::class);

        RateLimiter::clear('register|127.0.0.1');

        for ($i = 1; $i <= 3; $i++) {
            $this->postJson('/register', [
                'first_name' => 'Jane',
                'email' => "jane{$i}@example.com",
                'password' => 'Sup3rS3cret!2026',
                'password_confirmation' => 'Sup3rS3cret!2026',
                'terms_and_conditions' => true,
            ])->assertRedirect();

            Auth::logout();
            $this->flushSession();
        }

        $this->postJson('/register', [
            'first_name' => 'Jane',
            'email' => 'jane4@example.com',
            'password' => 'Sup3rS3cret!2026',
            'password_confirmation' => 'Sup3rS3cret!2026',
            'terms_and_conditions' => true,
        ])->assertStatus(429);
    }
}

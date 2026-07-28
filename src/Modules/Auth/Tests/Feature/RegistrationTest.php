<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ])->assertSuccessful();

        $user = User::query()->where('email', 'jane@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('USER'));
        $this->assertNull($user->email_verified_at, 'Email must require verification.');
    }

    public function test_registration_is_throttled_to_three_attempts_per_ip_per_hour(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->postJson('/register', [
                'first_name' => 'Jane',
                'email' => "jane{$i}@example.com",
                'password' => 'Sup3rS3cret!2026',
                'password_confirmation' => 'Sup3rS3cret!2026',
                'terms_and_conditions' => true,
            ])->assertSuccessful();
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

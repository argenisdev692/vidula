<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_issues_a_token_for_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => Hash::make('Sup3rS3cret!2026'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'Sup3rS3cret!2026',
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'expires_at', 'user' => ['uuid', 'email']]);

        $this->assertDatabaseHas('login_attempts', [
            'email' => 'jane@example.com',
            'successful' => true,
            'guard' => 'api',
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->getKey(),
            'name' => 'phpunit',
        ]);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'jane@example.com',
            'password' => Hash::make('Sup3rS3cret!2026'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422);

        $this->assertDatabaseHas('login_attempts', [
            'email' => 'jane@example.com',
            'successful' => false,
        ]);
    }

    public function test_login_blocks_unverified_email(): void
    {
        User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
            'password' => Hash::make('Sup3rS3cret!2026'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'unverified@example.com',
            'password' => 'Sup3rS3cret!2026',
        ])->assertStatus(403);
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $token = $user->createToken('phpunit')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'jane@example.com');
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('phpunit')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_refresh_rotates_the_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('phpunit')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/refresh')
            ->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'expires_at']);

        // Old token revoked, exactly one fresh token remains.
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }
}

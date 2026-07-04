<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SessionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_active_api_tokens(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('device-1')->plainTextToken;
        $user->createToken('device-2');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/sessions')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_revokes_other_api_tokens_keeping_the_current_one(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('device-1')->plainTextToken;
        $user->createToken('device-2');
        $user->createToken('device-3');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/auth/sessions/others')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_revokes_a_specific_api_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('device-1')->plainTextToken;
        $second = $user->createToken('device-2')->accessToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/auth/sessions/{$second->getKey()}")
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $second->getKey()]);
    }
}

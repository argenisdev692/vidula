<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class IdleLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_idle_logout_signs_the_user_out_and_flags_the_login_screen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/session/idle-logout', ['intended' => '/dashboard'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('expired', true)
            ->assertSessionHas('url.intended', '/dashboard');

        $this->assertGuest();
    }

    public function test_idle_logout_rejects_an_off_site_intended_path(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/session/idle-logout', ['intended' => '//evil.example.com/phish'])
            ->assertRedirect(route('login'))
            ->assertSessionMissing('url.intended');

        $this->assertGuest();
    }

    public function test_idle_logout_rejects_an_intended_auth_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/session/idle-logout', ['intended' => '/login'])
            ->assertRedirect(route('login'))
            ->assertSessionMissing('url.intended');
    }

    public function test_idle_logout_keeps_a_local_path_with_query_string(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/session/idle-logout', ['intended' => '/dashboard?tab=security'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', '/dashboard?tab=security');
    }

    public function test_sign_out_everywhere_logs_the_current_user_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/sessions/all')
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertGuest();
    }

    public function test_idle_logout_requires_authentication(): void
    {
        $this->post('/session/idle-logout', ['intended' => '/dashboard'])
            ->assertRedirect(route('login'));
    }
}

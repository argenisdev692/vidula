<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

final class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_callback_creates_a_new_verified_user_and_logs_in(): void
    {
        $this->fakeProvider('google', $this->socialUser('123', 'new@example.com'));

        $this->get('/auth/google/callback')->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('linked_social_accounts', [
            'provider' => 'google',
            'provider_user_id' => '123',
        ]);

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue($user->hasRole('USER'));
    }

    public function test_callback_links_to_an_existing_verified_user(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $this->fakeProvider('google', $this->socialUser('999', 'jane@example.com'));

        $this->get('/auth/google/callback')->assertRedirect('/dashboard');

        $this->assertSame(1, User::query()->count(), 'No new user should be created.');
        $this->assertDatabaseHas('linked_social_accounts', [
            'user_id' => $user->getKey(),
            'provider' => 'google',
            'provider_user_id' => '999',
        ]);
    }

    public function test_callback_refuses_linking_to_an_unverified_existing_email(): void
    {
        User::factory()->unverified()->create(['email' => 'jane@example.com']);
        $this->fakeProvider('google', $this->socialUser('555', 'jane@example.com'));

        $this->get('/auth/google/callback')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseMissing('linked_social_accounts', ['provider_user_id' => '555']);
    }

    public function test_unsupported_provider_is_not_found(): void
    {
        $this->get('/auth/twitter/redirect')->assertNotFound();
    }

    private function socialUser(string $id, string $email): SocialiteUser
    {
        $user = (new SocialiteUser)->map([
            'id' => $id,
            'name' => 'New User',
            'nickname' => 'newuser',
            'email' => $email,
            'avatar' => 'https://avatar.test/a.png',
        ]);
        $user->token = 'provider-token';
        $user->refreshToken = 'provider-refresh';
        $user->expiresIn = 3600;

        return $user;
    }

    private function fakeProvider(string $provider, SocialiteUser $socialUser): void
    {
        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
    }
}

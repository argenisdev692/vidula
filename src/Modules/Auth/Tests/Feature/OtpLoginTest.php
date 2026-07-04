<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Infrastructure\Notifications\QueuedOneTimePasswordNotification;
use Spatie\OneTimePasswords\Support\OriginInspector\DoNotEnforceOrigin;
use Spatie\OneTimePasswords\Support\OriginInspector\OriginEnforcer;
use Tests\TestCase;

final class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Stateless API OTP: do not bind the code to the request origin.
        config()->set('one-time-passwords.origin_enforcer', DoNotEnforceOrigin::class);
        $this->app->bind(OriginEnforcer::class, DoNotEnforceOrigin::class);
    }

    public function test_requesting_an_otp_sends_a_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'jane@example.com']);

        $this->postJson('/api/auth/otp/request', ['email' => 'jane@example.com'])->assertOk();

        Notification::assertSentTo($user, QueuedOneTimePasswordNotification::class);
    }

    public function test_requesting_an_otp_for_unknown_email_is_generic(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/otp/request', ['email' => 'nobody@example.com'])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_login_with_a_valid_otp_returns_a_token(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $otp = $user->createOneTimePassword();

        $this->postJson('/api/auth/otp/login', [
            'email' => 'jane@example.com',
            'one_time_password' => $otp->password,
        ])->assertOk()->assertJsonStructure(['token', 'token_type', 'expires_at', 'user']);

        $this->assertDatabaseHas('login_attempts', [
            'email' => 'jane@example.com',
            'successful' => true,
            'guard' => 'api',
        ]);
    }

    public function test_login_with_an_invalid_otp_fails(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $user->createOneTimePassword();

        $this->postJson('/api/auth/otp/login', [
            'email' => 'jane@example.com',
            'one_time_password' => '000000',
        ])->assertStatus(422);
    }
}

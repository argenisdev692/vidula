<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Infrastructure\Notifications\PasswordChangedNotification;
use Modules\Auth\Infrastructure\Notifications\QueuedOneTimePasswordNotification;
use Spatie\OneTimePasswords\Support\OriginInspector\DoNotEnforceOrigin;
use Spatie\OneTimePasswords\Support\OriginInspector\OriginEnforcer;
use Tests\TestCase;

final class PasswordResetOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Stateless API OTP: do not bind the code to the request origin.
        config()->set('one-time-passwords.origin_enforcer', DoNotEnforceOrigin::class);
        $this->app->bind(OriginEnforcer::class, DoNotEnforceOrigin::class);
    }

    public function test_requesting_a_reset_sends_a_six_digit_code(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'jane@example.com']);

        $this->postJson('/api/auth/password/forgot', ['email' => 'jane@example.com'])->assertOk();

        Notification::assertSentTo($user, QueuedOneTimePasswordNotification::class);
    }

    public function test_requesting_a_reset_for_unknown_email_is_generic(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/password/forgot', ['email' => 'nobody@example.com'])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_reset_with_a_valid_code_sets_the_new_password_and_verifies_email(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create([
            'email' => 'jane@example.com',
            'password' => Hash::make('OldP@ssw0rd123!'),
        ]);
        $otp = $user->createOneTimePassword();

        $this->postJson('/api/auth/password/reset', [
            'email' => 'jane@example.com',
            'one_time_password' => $otp->password,
            'password' => 'BrandN3w!Secret2026',
            'password_confirmation' => 'BrandN3w!Secret2026',
        ])->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('BrandN3w!Secret2026', $user->password));
        $this->assertNotNull($user->email_verified_at);
        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }

    public function test_reset_with_an_invalid_code_fails_and_keeps_the_old_password(): void
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => Hash::make('OldP@ssw0rd123!'),
        ]);
        $user->createOneTimePassword();

        $this->postJson('/api/auth/password/reset', [
            'email' => 'jane@example.com',
            'one_time_password' => '000000',
            'password' => 'BrandN3w!Secret2026',
            'password_confirmation' => 'BrandN3w!Secret2026',
        ])->assertStatus(422);

        $user->refresh();
        $this->assertTrue(Hash::check('OldP@ssw0rd123!', $user->password));
        $this->assertDatabaseHas('login_attempts', [
            'email' => 'jane@example.com',
            'successful' => false,
            'guard' => 'api',
        ]);
    }
}

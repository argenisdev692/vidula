<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Infrastructure\Notifications\SecurityWarningNotification;
use Spatie\OneTimePasswords\Support\OriginInspector\DoNotEnforceOrigin;
use Spatie\OneTimePasswords\Support\OriginInspector\OriginEnforcer;
use Tests\TestCase;

/**
 * Wrong 6-digit OTP codes must feed the SAME persistent account-lockout counter
 * as the password path: 3 strikes -> account locked for 1 hour + warning email.
 * Once locked, even a valid code is refused.
 */
final class OtpLockoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // OTP codes are not bound to a request origin in the test transport.
        config()->set('one-time-passwords.origin_enforcer', DoNotEnforceOrigin::class);
        $this->app->bind(OriginEnforcer::class, DoNotEnforceOrigin::class);

        // Pin the policy so the test is independent of env overrides.
        config()->set('security.lockout.max_attempts', 3);
        config()->set('security.lockout.decay_minutes', 60);
    }

    public function test_three_wrong_otp_codes_lock_the_account_and_send_email(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'target@example.com']);

        // Three wrong codes cross the lockout threshold (3).
        for ($i = 1; $i <= 3; $i++) {
            $this->from('/auth/otp')
                ->post('/auth/otp/login', [
                    'email' => 'target@example.com',
                    'one_time_password' => '000000',
                ])
                ->assertSessionHasErrors('one_time_password');
        }

        // The threshold-crossing failure emails the security warning exactly once.
        Notification::assertSentToTimes($user, SecurityWarningNotification::class, 1);

        // Even a VALID code is refused while the account is locked.
        $otp = $user->createOneTimePassword();

        $this->from('/auth/otp')
            ->post('/auth/otp/login', [
                'email' => 'target@example.com',
                'one_time_password' => $otp->password,
            ])
            ->assertSessionHasErrors('one_time_password');

        $this->assertGuest();
    }

    public function test_valid_otp_below_the_threshold_still_logs_in_and_clears_the_counter(): void
    {
        $user = User::factory()->create(['email' => 'target@example.com']);

        // Two wrong codes (below the threshold) do not lock the account.
        for ($i = 1; $i <= 2; $i++) {
            $this->from('/auth/otp')
                ->post('/auth/otp/login', [
                    'email' => 'target@example.com',
                    'one_time_password' => '000000',
                ])
                ->assertSessionHasErrors('one_time_password');
        }

        // A valid code authenticates and (via the Login event) clears the counter.
        $otp = $user->createOneTimePassword();

        $this->from('/auth/otp')
            ->post('/auth/otp/login', [
                'email' => 'target@example.com',
                'one_time_password' => $otp->password,
            ])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }
}

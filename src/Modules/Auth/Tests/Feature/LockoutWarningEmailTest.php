<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Infrastructure\Notifications\SecurityWarningNotification;
use Tests\TestCase;

final class LockoutWarningEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('security.lockout.max_attempts', 3);
        config()->set('security.lockout.decay_minutes', 60);
        Notification::fake();
    }

    public function test_three_failed_attempts_lock_the_account_and_send_a_warning(): void
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => Hash::make('Sup3rS3cret!2026'),
        ]);

        // First two failures are plain invalid-credentials (422).
        foreach ([1, 2] as $ignored) {
            $this->postJson('/api/auth/login', [
                'email' => 'jane@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        // Third failure crosses the threshold -> locked (429) + warning email.
        $this->postJson('/api/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429)->assertHeader('Retry-After');

        Notification::assertSentTo($user, SecurityWarningNotification::class);
    }
}

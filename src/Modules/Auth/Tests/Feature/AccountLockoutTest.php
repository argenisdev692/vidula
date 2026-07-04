<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AccountLockoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_locks_after_ten_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'target@example.com',
            'password' => Hash::make('Sup3rS3cret!2026'),
        ]);

        // Attempts 1-9 are rejected as invalid credentials (422).
        for ($i = 1; $i <= 9; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'target@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        // The 10th failure crosses the threshold -> account locked (429).
        $this->postJson('/api/auth/login', [
            'email' => 'target@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429)->assertHeader('Retry-After');

        // Even the correct password is now refused while locked.
        $this->postJson('/api/auth/login', [
            'email' => 'target@example.com',
            'password' => 'Sup3rS3cret!2026',
        ])->assertStatus(429);
    }
}

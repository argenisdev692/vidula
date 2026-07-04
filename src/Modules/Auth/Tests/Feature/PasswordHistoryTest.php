<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Infrastructure\Auth\Password\PasswordHistory;
use Modules\Auth\Infrastructure\Notifications\PasswordChangedNotification;
use Tests\TestCase;

final class PasswordHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_cannot_reuse_a_recent_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldP@ssw0rd123!')]);
        app(PasswordHistory::class)->record($user, $user->password);

        $this->actingAs($user)->putJson('/user/password', [
            'current_password' => 'OldP@ssw0rd123!',
            'password' => 'OldP@ssw0rd123!',
            'password_confirmation' => 'OldP@ssw0rd123!',
        ])->assertStatus(422);
    }

    public function test_setting_a_new_password_records_history_and_notifies(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldP@ssw0rd123!')]);

        $this->actingAs($user)->putJson('/user/password', [
            'current_password' => 'OldP@ssw0rd123!',
            'password' => 'BrandN3w!Secret2026',
            'password_confirmation' => 'BrandN3w!Secret2026',
        ])->assertSuccessful();

        $user->refresh();
        $this->assertTrue(Hash::check('BrandN3w!Secret2026', $user->password));
        $this->assertDatabaseHas('password_histories', ['user_id' => $user->getKey()]);
        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }
}

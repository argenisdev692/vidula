<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Infrastructure\Notifications\NewDeviceNotification;
use Tests\TestCase;

final class NewDeviceAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_login_from_a_new_device_sends_an_alert(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => Hash::make('Sup3rS3cret!2026'),
        ]);

        $this->post('/login', [
            'email' => 'jane@example.com',
            'password' => 'Sup3rS3cret!2026',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, NewDeviceNotification::class);
    }
}

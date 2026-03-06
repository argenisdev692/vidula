<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Domain\Events\OtpGenerated;
use Modules\Auth\Domain\Events\UserLoggedIn;
use Modules\Notifications\Infrastructure\Notifications\SendOtpNotification;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;
use Tests\TestCase;

final class OtpFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_an_otp_and_dispatches_the_domain_event(): void
    {
        Notification::fake();
        Event::fake([OtpGenerated::class]);

        $user = UserEloquentModel::factory()->create();

        $response = $this->from('/login')->post(route('login.otp.send'), [
            'identifier' => $user->email,
        ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHas('success', 'If the account exists, an OTP has been sent.');

        Notification::assertSentTo($user, SendOtpNotification::class);

        Event::assertDispatched(OtpGenerated::class, function (OtpGenerated $event) use ($user): bool {
            return $event->aggregateId === $user->uuid
                && $event->identifier === $user->email
                && $event->channel === 'email';
        });
    }

    public function test_it_verifies_an_otp_logs_the_user_in_and_dispatches_the_login_event(): void
    {
        Event::fake([UserLoggedIn::class]);

        $user = UserEloquentModel::factory()->create();

        Cache::put(
            'otp:' . strtolower((string) $user->email),
            Hash::make('123456'),
            600,
        );

        $response = $this->from('/login')->post(route('login.otp.verify'), [
            'identifier' => $user->email,
            'code' => '123456',
        ]);

        $response
            ->assertRedirect('/dashboard')
            ->assertSessionHas('success', 'Authentication successful.');

        $this->assertAuthenticatedAs($user);

        Event::assertDispatched(UserLoggedIn::class, function (UserLoggedIn $event) use ($user): bool {
            return $event->aggregateId === $user->uuid
                && $event->userId === $user->id
                && $event->provider === 'otp';
        });
    }
}

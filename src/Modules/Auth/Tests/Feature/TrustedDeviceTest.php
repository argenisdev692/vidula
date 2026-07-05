<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Modules\Auth\Infrastructure\Auth\TwoFactor\TrustedDeviceManager;
use Tests\TestCase;

final class TrustedDeviceTest extends TestCase
{
    use RefreshDatabase;

    private TrustedDeviceManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = app(TrustedDeviceManager::class);
    }

    public function test_trusting_a_device_persists_a_row_and_queues_a_cookie(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/two-factor/trust-device', 'POST');
        $request->headers->set('User-Agent', 'phpunit');

        $device = $this->manager->trust($request, $user);

        $this->assertDatabaseHas('trusted_devices', [
            'uuid' => $device->uuid,
            'user_id' => $user->getKey(),
        ]);

        $queued = collect(Cookie::getQueuedCookies())
            ->contains(fn ($cookie): bool => $cookie->getName() === 'two_factor_trust_'.$user->getKey());
        $this->assertTrue($queued, 'A trusted-device cookie should be queued.');
    }

    public function test_is_trusted_matches_a_valid_cookie(): void
    {
        $user = User::factory()->create();
        $token = 'a-known-raw-token';

        $user->trustedDevices()->create([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(30),
        ]);

        $request = Request::create('/');
        $request->cookies->set('two_factor_trust_'.$user->getKey(), $token);

        $this->assertTrue($this->manager->isTrusted($request, $user));
    }

    public function test_is_trusted_rejects_an_expired_device(): void
    {
        $user = User::factory()->create();
        $token = 'expired-token';

        $user->trustedDevices()->create([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->subDay(),
        ]);

        $request = Request::create('/');
        $request->cookies->set('two_factor_trust_'.$user->getKey(), $token);

        $this->assertFalse($this->manager->isTrusted($request, $user));
    }

    public function test_challenge_page_trusts_the_device_over_json(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/two-factor/trust-device')
            ->assertOk()
            ->assertJson(['status' => 'trusted-device-added']);

        $this->assertDatabaseHas('trusted_devices', ['user_id' => $user->getKey()]);
    }

    public function test_revoke_soft_deletes_the_device(): void
    {
        $user = User::factory()->create();
        $device = $user->trustedDevices()->create([
            'token_hash' => hash('sha256', 'tok'),
            'expires_at' => now()->addDays(30),
        ]);

        $this->manager->revoke($user, (string) $device->uuid);

        $this->assertSoftDeleted('trusted_devices', ['uuid' => $device->uuid]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Web;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Auth\Domain\Events\TrustedDeviceAdded;
use Modules\Auth\Infrastructure\Auth\TwoFactor\TrustedDeviceManager;
use Shared\Domain\Ports\AuditPort;

use function event;

/**
 * Manages the user's 30-day trusted devices. The frontend calls `trust`
 * immediately after a successful 2FA challenge when the user ticked
 * "trust this device", and `revoke` from the security settings page.
 */
final readonly class TrustedDeviceController
{
    public function __construct(
        private TrustedDeviceManager $devices,
        private AuditPort $audit,
    ) {}

    public function trust(Request $request): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $device = $this->devices->trust($request, $user);

        $this->audit->log(
            event: 'auth.trusted_device_added',
            subject: $user,
            properties: ['device_uuid' => $device->uuid, 'ip_address' => $request->ip()],
            causer: $user,
            logName: 'auth',
        );

        event(new TrustedDeviceAdded((string) $user->uuid, (string) $device->uuid));

        // The two-factor challenge page trusts the device with a JSON XHR right
        // after sign-in (no navigation); the settings page uses the Inertia flow.
        return match ($request->expectsJson()) {
            true => response()->json(['status' => 'trusted-device-added']),
            false => back()->with('status', 'trusted-device-added'),
        };
    }

    public function revoke(Request $request, string $uuid): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->devices->revoke($user, $uuid);

        $this->audit->log(
            event: 'auth.trusted_device_revoked',
            subject: $user,
            properties: ['device_uuid' => $uuid],
            causer: $user,
            logName: 'auth',
        );

        return back()->with('status', 'trusted-device-revoked');
    }
}

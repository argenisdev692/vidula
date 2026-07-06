<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Web;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Auth\Application\DTOs\ProfileData;
use Modules\Auth\Infrastructure\Session\SessionRegistry;
use Shared\Domain\Ports\StoragePort;
use Throwable;

/**
 * Renders the authenticated user's self-service profile page — a single screen
 * that fuses profile details, security (password, two-factor, active sessions,
 * trusted devices) and roles. Editing the profile reuses Fortify's
 * `PUT /user/profile-information` (App\Actions\Fortify\UpdateUserProfileInformation),
 * password changes reuse `PUT /user/password`, the photo lives on
 * ProfilePhotoController, and the two-factor enrollment (QR / secret / recovery
 * codes) is loaded on demand from Fortify's JSON endpoints. This controller only
 * assembles the read models the page needs to render inline.
 */
final readonly class ProfileController
{
    public function __construct(
        private StoragePort $storage,
        private SessionRegistry $sessions,
    ) {}

    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Auth/Profile', [
            'profile' => ProfileData::fromModel($user, $this->profilePhotoUrl($user)),
            'sessions' => $this->sessions->listForUser($user, $request->session()->getId()),
            'twoFactor' => [
                'enabled' => $user->hasEnabledTwoFactorAuthentication(),
                'confirmed' => $user->two_factor_confirmed_at !== null,
            ],
            'trustedDevices' => $user->trustedDevices()
                ->where('expires_at', '>', now())
                ->orderByDesc('last_used_at')
                ->get(['uuid', 'user_agent', 'ip_address', 'last_used_at', 'expires_at']),
        ]);
    }

    /**
     * Realtime uniqueness check for the profile form's `username` / `email`
     * fields. Only these two allowlisted columns can be queried, the current
     * user is always excluded (so an unchanged value reads as available), and
     * malformed values short-circuit to unavailable. Rate-limited at the route.
     */
    public function availability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', 'string', 'in:username,email'],
            'value' => ['required', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $field = $validated['field'];
        $value = $validated['value'];

        if ($field === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return response()->json(['available' => false]);
        }

        if ($field === 'username' && mb_strlen($value) > 15) {
            return response()->json(['available' => false]);
        }

        $taken = User::query()
            ->where($field, $value)
            ->whereKeyNot($user->getKey())
            ->exists();

        return response()->json(['available' => ! $taken]);
    }

    /**
     * Signed, time-limited URL for the user's private R2 profile photo (null when
     * none is set). Mirrors HandleInertiaRequests::profilePhotoUrl — failures to
     * sign degrade to null rather than breaking the page render.
     */
    private function profilePhotoUrl(User $user): ?string
    {
        if ($user->profile_photo_path === null) {
            return null;
        }

        try {
            return $this->storage->temporaryUrl(
                $user->profile_photo_path,
                CarbonImmutable::now()->addMinutes(15),
            );
        } catch (Throwable) {
            return null;
        }
    }
}

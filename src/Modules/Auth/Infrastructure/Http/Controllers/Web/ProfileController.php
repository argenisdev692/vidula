<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Web;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Auth\Application\DTOs\ProfileData;
use Shared\Domain\Ports\StoragePort;
use Throwable;

/**
 * Renders the authenticated user's self-service profile page. Editing the
 * profile itself reuses Fortify's `PUT /user/profile-information`
 * (App\Actions\Fortify\UpdateUserProfileInformation) and password changes reuse
 * `PUT /user/password`; the photo lives on ProfilePhotoController. This
 * controller only assembles the read model (with a signed R2 photo URL).
 */
final readonly class ProfileController
{
    public function __construct(private StoragePort $storage) {}

    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Auth/Profile', [
            'profile' => ProfileData::fromModel($user, $this->profilePhotoUrl($user)),
        ]);
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

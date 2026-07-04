<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Web;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Auth\Application\Commands\DeleteProfilePhotoHandler;
use Modules\Auth\Application\Commands\UpdateProfilePhotoHandler;
use Modules\Auth\Application\DTOs\UpdateProfilePhotoData;
use Shared\Domain\Ports\AuditPort;

/**
 * Self-service profile photo management for the authenticated user. Uploads land
 * on Cloudflare R2 (private) via the StoragePort-backed handlers; the signed
 * display URL is shared to the frontend by HandleInertiaRequests. Consistent with
 * the other authenticated settings controllers (sessions, trusted devices).
 */
final readonly class ProfilePhotoController
{
    public function __construct(
        private UpdateProfilePhotoHandler $updatePhoto,
        private DeleteProfilePhotoHandler $deletePhoto,
        private AuditPort $audit,
    ) {}

    public function update(UpdateProfilePhotoData $data, Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $path = $this->updatePhoto->handle((string) $user->uuid, $user->profile_photo_path, $data->photo);

        $user->forceFill(['profile_photo_path' => $path])->save();

        $this->audit->log(
            event: 'auth.profile_photo_updated',
            subject: $user,
            causer: $user,
            logName: 'auth',
        );

        return back()->with('status', 'profile-photo-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->deletePhoto->handle($user->profile_photo_path);

        $user->forceFill(['profile_photo_path' => null])->save();

        $this->audit->log(
            event: 'auth.profile_photo_deleted',
            subject: $user,
            causer: $user,
            logName: 'auth',
        );

        return back()->with('status', 'profile-photo-deleted');
    }
}

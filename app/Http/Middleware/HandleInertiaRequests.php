<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Shared\Domain\Ports\StoragePort;
use Throwable;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user?->only(['id', 'first_name', 'last_name', 'email']),
                'profile_photo_url' => $this->profilePhotoUrl($user),
                'permissions' => $user ? $user->getAllPermissions()->pluck('name')->all() : [],
                'roles' => $user ? $user->getRoleNames()->all() : [],
            ],
        ];
    }

    /**
     * Signed, time-limited URL for the user's private R2 profile photo (null when
     * none is set). Failures to sign (e.g. a disk that cannot presign) degrade to
     * null rather than breaking every authenticated page render.
     */
    private function profilePhotoUrl(?User $user): ?string
    {
        if ($user?->profile_photo_path === null) {
            return null;
        }

        try {
            return app(StoragePort::class)->temporaryUrl(
                $user->profile_photo_path,
                CarbonImmutable::now()->addMinutes(15),
            );
        } catch (Throwable) {
            return null;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Shared\Domain\Ports\StoragePort;
use Shared\Infrastructure\Company\CompanyProfile;
use Throwable;

class HandleInertiaRequests extends Middleware
{
    public function __construct(private StoragePort $storage) {}

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
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
                'error' => fn (): ?string => $request->session()->get('error'),
            ],
            'company' => $this->companyBranding(),
            'config' => [
                'google_maps_api_key' => (string) config('services.googlemaps.key'),
                'app_title_description' => (string) config('app.title_description'),
            ],
        ];
    }

    /**
     * DB-driven company branding (name + logo URLs) for the hero and navbar.
     * Cached inside CompanyProfile, so this adds no per-request query.
     *
     * @return array{name: string, logo_url: string, logo_white_url: string, mark_url: string}
     */
    private function companyBranding(): array
    {
        $company = CompanyProfile::data();

        return [
            'name' => $company['name'],
            'logo_url' => $company['logo_url'],
            'logo_white_url' => $company['logo_white_url'],
            'mark_url' => $company['mark_url'],
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
            return $this->storage->temporaryUrl(
                $user->profile_photo_path,
                CarbonImmutable::now()->addMinutes(15),
            );
        } catch (Throwable) {
            return null;
        }
    }
}

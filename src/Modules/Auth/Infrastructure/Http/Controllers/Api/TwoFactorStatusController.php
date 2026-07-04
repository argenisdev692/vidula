<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Authentication
 */
final readonly class TwoFactorStatusController
{
    /**
     * Get the caller's two-factor authentication status
     *
     * @authenticated
     *
     * @response 200 scenario="Success" {"data":{"enabled":true,"confirmed":true,"trusted_devices":2}}
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => [
                'enabled' => $user->hasEnabledTwoFactorAuthentication(),
                'confirmed' => $user->two_factor_confirmed_at !== null,
                'trusted_devices' => $user->trustedDevices()->where('expires_at', '>', now())->count(),
            ],
        ]);
    }
}

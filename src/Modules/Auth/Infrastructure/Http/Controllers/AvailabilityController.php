<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Shared\Application\Queries\CheckUserFieldAvailability;

/**
 * Realtime username/email uniqueness check for the guest register form and the
 * authenticated self-service profile form (web + Sanctum API). Transport-agnostic
 * (returns JSON), so it is mounted on both route files.
 *
 * The row to ignore is derived server-side from the session, never from client
 * input: a signed-in user always excludes their own record (so an unchanged
 * value reads as available), while a guest ignores nothing. Rate-limited at the
 * route to blunt account-enumeration probing.
 */
final readonly class AvailabilityController
{
    public function __construct(private CheckUserFieldAvailability $check) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', 'string', 'in:username,email'],
            'value' => ['required', 'string', 'max:255'],
        ]);

        /** @var User|null $user */
        $user = $request->user();

        $available = ($this->check)(
            $validated['field'],
            $validated['value'],
            $user?->uuid,
        );

        return response()->json(['available' => $available]);
    }
}

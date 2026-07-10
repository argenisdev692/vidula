<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Shared\Application\Queries\CheckUserFieldAvailability;

/**
 * Realtime username/email uniqueness check for the admin create/edit user forms
 * (web + Sanctum API). Authorized by `permission:CREATE_USERS|UPDATE_USERS` at
 * the route.
 *
 * On the edit form the client passes `ignore` = the edited user's UUID so their
 * own current value reads as available; the create form omits it. The value is
 * validated as a UUID before it reaches the query.
 */
final readonly class UserAvailabilityController
{
    public function __construct(private CheckUserFieldAvailability $check) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', 'string', 'in:username,email'],
            'value' => ['required', 'string', 'max:255'],
            'ignore' => ['nullable', 'uuid'],
        ]);

        $available = ($this->check)(
            $validated['field'],
            $validated['value'],
            $validated['ignore'] ?? null,
        );

        return response()->json(['available' => $available]);
    }
}

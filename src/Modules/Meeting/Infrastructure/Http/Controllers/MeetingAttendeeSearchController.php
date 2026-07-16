<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Meeting\Application\Queries\SearchAttendeesHandler;

/**
 * Attendee typeahead. Gated by `CREATE_MEETINGS`/`UPDATE_MEETINGS` (a staff
 * member composing a meeting), NOT by VIEW_ANY_USERS/VIEW_ANY_APPOINTMENTS/
 * VIEW_ANY_CONTACT_SUPPORTS — the handler returns only minimal fields, so no
 * extra module permission is required to search across them (research.md §5).
 */
final readonly class MeetingAttendeeSearchController
{
    public function __invoke(Request $request, SearchAttendeesHandler $search): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
            'type' => ['nullable', 'string', 'in:user,lead,contact'],
        ]);

        return response()->json(['data' => $search->handle($validated['q'], $validated['type'] ?? null)]);
    }
}

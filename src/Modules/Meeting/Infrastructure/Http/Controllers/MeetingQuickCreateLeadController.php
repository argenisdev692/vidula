<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Meeting\Application\Commands\QuickCreateMeetingLeadHandler;
use Modules\Meeting\Application\DTOs\QuickCreateMeetingLeadData;

/**
 * Quick-create a Lead from the meeting attendee picker when search finds no
 * existing User / Lead / Contact. Gated by CREATE_MEETINGS|UPDATE_MEETINGS —
 * not CREATE_APPOINTMENTS — because this is a Meeting UX affordance that
 * returns only the minimal attendee option (OWASP data minimization).
 */
final readonly class MeetingQuickCreateLeadController
{
    public function __invoke(
        QuickCreateMeetingLeadData $data,
        QuickCreateMeetingLeadHandler $create,
    ): JsonResponse {
        return response()->json(['data' => $create->handle($data)], 201);
    }
}

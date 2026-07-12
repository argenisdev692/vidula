<?php

declare(strict_types=1);

namespace Modules\Appointment\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Application\Commands\BookAppointmentHandler;
use Modules\Appointment\Application\DTOs\BookAppointmentData;
use Spatie\Honeypot\Exceptions\SpamException;
use Spatie\Honeypot\Honeypot;
use Spatie\Honeypot\SpamProtection;

/**
 * Public (unauthenticated) booking REST API consumed by the Astro marketing
 * landing page. Stateless — no session, no CSRF — so it can be called
 * cross-origin. Documented by Scramble via the injected {@see BookAppointmentData}
 * (request body) + explicit `JsonResponse` return types.
 *
 * Two anti-spam layers, mirroring the ContactSupport public API:
 *   1. Honeypot (opt-in): if the landing page sends the trap fields obtained
 *      from {@see self::honeypot()}, they are enforced here — a tripped trap is
 *      dropped silently with the SAME success response, so a bot never learns
 *      which layer caught it.
 *   2. SpamGuard content scoring inside {@see BookAppointmentHandler} — flags
 *      (is_spam), never rejects, so a false positive can still be reviewed.
 *
 * Scheduling validation (past date / closed day / conflict) and the
 * one-active-lead-per-email guard happen inside {@see BookAppointmentHandler}
 * and surface as a normal 422.
 */
final readonly class PublicAppointmentApiController
{
    /**
     * Book an appointment.
     *
     * Accepts a scheduling request from the public landing page and stores the
     * lead, validating the requested date/time against the current
     * availability calendar (never in the past, inside an open window, not
     * already taken).
     */
    public function store(
        BookAppointmentData $data,
        Request $request,
        BookAppointmentHandler $book,
        SpamProtection $spamProtection,
    ): JsonResponse {
        $accepted = response()->json([
            'message' => __('Thanks! Your appointment request has been received.'),
        ], 201);

        try {
            $spamProtection->check($request->all());
        } catch (SpamException) {
            return $accepted;
        }

        $book->handle($data);

        return $accepted;
    }

    /**
     * Get honeypot fields.
     *
     * Returns the field names + encrypted timestamp the landing page must embed
     * as hidden inputs and echo back on submit.
     */
    public function honeypot(Honeypot $honeypot): JsonResponse
    {
        return response()->json($honeypot->toArray());
    }
}

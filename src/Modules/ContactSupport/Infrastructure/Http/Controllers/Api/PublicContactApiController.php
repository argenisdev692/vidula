<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ContactSupport\Application\Commands\SubmitContactRequestHandler;
use Modules\ContactSupport\Application\DTOs\ContactSupportData;
use Spatie\Honeypot\Exceptions\SpamException;
use Spatie\Honeypot\Honeypot;
use Spatie\Honeypot\SpamProtection;

/**
 * Public (unauthenticated) contact-support REST API for the marketing landing
 * page. Stateless — no session, no CSRF — so it can be called cross-origin.
 * Documented by Scramble via the injected {@see ContactSupportData} (request
 * body) + explicit `JsonResponse` return types.
 *
 * Two anti-spam layers, both silent (a bot never learns which one tripped):
 *   1. Honeypot (opt-in): if the landing page sends the trap fields obtained
 *      from {@see self::honeypot()}, they are enforced here.
 *   2. SpamGuard content scoring inside {@see SubmitContactRequestHandler} —
 *      flags, never rejects. Plus a tight per-IP throttle on the route.
 *
 * Auth: `crm.token` middleware (CRM_API_TOKEN) — Astro server-side only.
 */
final readonly class PublicContactApiController
{
    /**
     * Submit a contact request.
     *
     * Accepts a contact-support submission from the public landing page and
     * stores it for the support team. Spam is flagged for review, never
     * rejected; the response is identical whether or not the message was
     * flagged, so automated senders gain no signal.
     */
    public function store(
        ContactSupportData $data,
        Request $request,
        SubmitContactRequestHandler $submit,
        SpamProtection $spamProtection,
    ): JsonResponse {
        $accepted = response()->json([
            'message' => __('Thanks! Your message has been received. We will get back to you shortly.'),
        ], 201);

        // Honeypot bot layer: only enforced when the client included the trap
        // fields. A tripped trap is dropped silently with the SAME success
        // response — the row is simply never stored.
        try {
            $spamProtection->check($request->all());
        } catch (SpamException) {
            return $accepted;
        }

        (void) $submit->handle($data);

        return $accepted;
    }

    /**
     * Get honeypot fields.
     *
     * Returns the field names + encrypted timestamp the landing page must embed
     * as hidden inputs and echo back on submit, so the honeypot bot filter can
     * validate the request. The `encryptedValidFrom` value is self-contained
     * (no session needed) and expires after the configured window.
     */
    public function honeypot(Honeypot $honeypot): JsonResponse
    {
        return response()->json($honeypot->toArray());
    }
}

<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Infrastructure\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\ContactSupport\Application\Commands\SubmitContactRequestHandler;
use Modules\ContactSupport\Application\DTOs\ContactSupportData;
use Spatie\Honeypot\Honeypot;

/**
 * Public (guest) contact form. `create` renders the Inertia page and hands the
 * honeypot field names + encrypted timestamp to the client so the hidden trap
 * fields can be injected into the POST payload. `store` is guarded by the
 * spatie `ProtectAgainstSpam` middleware (bot layer) + `throttle` and delegates
 * content scoring to {@see SubmitContactRequestHandler}. Both stay thin.
 */
final readonly class PublicContactController
{
    public function create(Honeypot $honeypot): InertiaResponse
    {
        return Inertia::render('contact/Index', [
            'honeypot' => $honeypot->toArray(),
        ]);
    }

    public function store(ContactSupportData $data, SubmitContactRequestHandler $submit): RedirectResponse
    {
        $submit->handle($data);

        // Always report success — a flagged (spam) row is stored silently for
        // review, never surfaced to the sender, so bots learn nothing.
        return back()->with('success', __('Thanks! Your message has been received. We will get back to you shortly.'));
    }
}

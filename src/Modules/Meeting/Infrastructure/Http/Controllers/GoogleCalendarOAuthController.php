<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Meeting\Infrastructure\GoogleCalendar\GoogleCalendarOAuthService;

/**
 * OAuth connect/callback for the shared Google Calendar account. Gated by
 * `auth` + `permission:VIEW_ANY_MEETINGS` + throttle (see Routes) — visit
 * `/google-calendar/oauth/connect` while logged in as elevated staff to
 * generate `storage/app/google-calendar/oauth-token.json`.
 */
final readonly class GoogleCalendarOAuthController
{
    public function connect(GoogleCalendarOAuthService $oauth): RedirectResponse
    {
        return redirect()->away($oauth->authorizationUrl());
    }

    public function callback(Request $request, GoogleCalendarOAuthService $oauth): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()
                ->route('meetings.index')
                ->with('error', (string) $request->string('error'));
        }

        if (! $request->filled('code')) {
            return redirect()
                ->route('meetings.index')
                ->with('error', __('Google did not return an authorization code.'));
        }

        $oauth->storeTokenFromCode((string) $request->string('code'));

        return redirect()
            ->route('meetings.index')
            ->with('success', __('Google Calendar connected successfully.'));
    }
}

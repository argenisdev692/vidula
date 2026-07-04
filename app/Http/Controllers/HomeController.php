<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Root entry point. Guests are sent to the sign-in screen; authenticated users
 * continue to the application home (`/dashboard`, matching the post-auth
 * redirect used by the OTP / social / activation flows).
 *
 * Kept as an invokable controller (not a route closure) so `route:cache`
 * can serialize it for production.
 */
final class HomeController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return Auth::check()
            ? redirect('/dashboard')
            : redirect()->route('login');
    }
}

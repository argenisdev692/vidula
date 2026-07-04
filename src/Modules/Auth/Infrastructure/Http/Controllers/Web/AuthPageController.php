<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Web;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders the Inertia auth pages (the GET "view" side). The POST/PUT/DELETE
 * actions stay with Fortify; these methods are wired to Fortify's *View
 * callbacks in AuthServiceProvider. The Vue page components are delivered in
 * the separate frontend pass (resources/js/Pages/auth/*).
 */
final readonly class AuthPageController
{
    public function login(Request $request): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => true,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function register(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function requestPasswordReset(Request $request): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Reset screen for the OTP flow: the user arrives from the "code sent" state
     * and submits email + 6-digit code + new password (no URL token).
     */
    public function resetPassword(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->query('email'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function verifyEmail(Request $request): Response
    {
        return Inertia::render('Auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function confirmPassword(): Response
    {
        return Inertia::render('Auth/ConfirmPassword');
    }

    public function otpLogin(Request $request): Response
    {
        return Inertia::render('Auth/OtpLogin', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function passwordExpired(): Response
    {
        return Inertia::render('Auth/PasswordExpired', [
            'expiryMonths' => (int) config('security.password.expiry_months', 3),
        ]);
    }

    public function twoFactorChallenge(): Response
    {
        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function twoFactorSetup(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Auth/TwoFactorSetup', [
            'enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'confirmed' => $user->two_factor_confirmed_at !== null,
            'trustedDevices' => $user->trustedDevices()
                ->where('expires_at', '>', now())
                ->orderByDesc('last_used_at')
                ->get(['uuid', 'user_agent', 'ip_address', 'last_used_at', 'expires_at']),
        ]);
    }
}

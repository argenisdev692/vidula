<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Application\Commands\SendOtp\SendOtpCommand;
use Modules\Auth\Application\Commands\SendOtp\SendOtpHandler;
use Modules\Auth\Application\Commands\VerifyOtp\VerifyOtpCommand;
use Modules\Auth\Application\Commands\VerifyOtp\VerifyOtpHandler;
use Modules\Auth\Domain\Exceptions\InvalidOtpException;
use Modules\Auth\Domain\Exceptions\UserNotFoundException;
use Modules\Auth\Infrastructure\Http\Requests\SendOtpRequest;
use Modules\Auth\Infrastructure\Http\Requests\VerifyOtpRequest;

/**
 * OtpController — Infrastructure HTTP adapter for OTP flows.
 *
 * Thin controller:
 * 1. Validates via FormRequest
 * 2. Maps to CQRS Command
 * 3. Delegates to Handler
 * 4. Catches domain exceptions → HTTP status codes
 * 5. Handles Auth::login() (infrastructure concern)
 */
final class OtpController extends Controller
{
    public function __construct(
        private readonly SendOtpHandler $sendOtpHandler,
        private readonly VerifyOtpHandler $verifyOtpHandler,
    ) {
    }

    /**
     * POST /login/otp/send
     */
    public function send(SendOtpRequest $request): RedirectResponse
    {
        try {
            $this->sendOtpHandler->handle(
                new SendOtpCommand(
                    identifier: $request->validated('identifier'),
                ),
            );
        } catch (UserNotFoundException) {
            // Don't reveal if user exists — return success anyway (OWASP)
        }

        return back()->with('success', 'If the account exists, an OTP has been sent.');
    }

    /**
     * POST /login/otp/verify
     */
    public function verify(VerifyOtpRequest $request)
    {
        try {
            $result = $this->verifyOtpHandler->handle(
                new VerifyOtpCommand(
                    identifier: $request->validated('identifier'),
                    code: $request->validated('code'),
                    ipAddress: $request->ip() ?? 'unknown',
                    userAgent: $request->userAgent() ?? 'unknown',
                ),
            );

            // Infrastructure concern: actual session login
            Auth::loginUsingId($result['user']->id, true);

            return redirect()->intended('/dashboard')
                ->with('success', 'Authentication successful.');
        } catch (UserNotFoundException) {
            return back()->withErrors([
                'identifier' => 'Invalid credentials.',
            ]);
        } catch (InvalidOtpException) {
            return back()->withErrors([
                'otp' => 'Invalid or expired OTP code.',
            ]);
        }
    }
}

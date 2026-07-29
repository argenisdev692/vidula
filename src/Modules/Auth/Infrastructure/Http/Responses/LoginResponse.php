<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Modules\Auth\Infrastructure\Http\Support\SafeIntended;
use Symfony\Component\HttpFoundation\Response;

/**
 * Post-password login redirect that refuses off-site url.intended values
 * (prevents bouncing users to Google/gmail after auth when the session was
 * poisoned or Host/X-Forwarded-Host was wrong).
 */
final class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false]);
        }

        return redirect()->to(SafeIntended::pull(Fortify::redirects('login', '/dashboard')));
    }
}

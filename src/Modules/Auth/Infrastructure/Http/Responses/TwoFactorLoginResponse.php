<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;
use Modules\Auth\Infrastructure\Http\Support\SafeIntended;
use Symfony\Component\HttpFoundation\Response;

/**
 * After a successful 2FA challenge, land on a safe same-origin path only.
 */
final class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        return redirect()->to(SafeIntended::pull(Fortify::redirects('login', '/dashboard')));
    }
}

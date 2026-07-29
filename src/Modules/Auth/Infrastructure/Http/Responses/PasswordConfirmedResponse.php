<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\PasswordConfirmedResponse as PasswordConfirmedResponseContract;
use Laravel\Fortify\Fortify;
use Modules\Auth\Infrastructure\Http\Support\SafeIntended;
use Symfony\Component\HttpFoundation\Response;

/**
 * After password confirmation, resume the intended action only when it is a
 * same-origin path — never an external host.
 */
final class PasswordConfirmedResponse implements PasswordConfirmedResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
        }

        return redirect()->to(
            SafeIntended::pull(Fortify::redirects('password-confirmation', '/dashboard')),
        );
    }
}

<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Shared\Providers\SharedServiceProvider;
use Symfony\Component\HttpFoundation\Response;

/**
 * Emits the OWASP-baseline security response headers plus a per-request,
 * nonce-based Content-Security-Policy.
 *
 * The nonce is resolved from the request-scoped `csp-nonce` container binding
 * (see {@see SharedServiceProvider}) and handed to Vite
 * BEFORE the view renders, so every `@vite` / `@viteReactRefresh` tag produced
 * by the Blade root view carries a matching `nonce` attribute. This keeps
 * `script-src` free of `'unsafe-inline'`.
 *
 * Registered ahead of the Inertia middleware in bootstrap/app.php so the
 * headers survive Inertia (SSR) responses.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var string $nonce */
        $nonce = app('csp-nonce');

        // Bind the nonce to Vite before the response is generated so emitted
        // asset tags inherit it.
        Vite::useCspNonce($nonce);

        $response = $next($request);

        foreach ($this->staticHeaders($request) as $header => $value) {
            $response->headers->set($header, $value);
        }

        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));

        return $response;
    }

    /**
     * Transport-agnostic hardening headers.
     *
     * @return array<string, string>
     */
    private function staticHeaders(Request $request): array
    {
        $headers = [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'Permissions-Policy' => (string) config('security.headers.permissions_policy'),
        ];

        // HSTS only over HTTPS to avoid pinning plain-HTTP local development.
        if ((bool) config('security.headers.hsts.enabled') && $request->isSecure()) {
            $value = 'max-age='.(int) config('security.headers.hsts.max_age');

            if ((bool) config('security.headers.hsts.include_subdomains')) {
                $value .= '; includeSubDomains';
            }

            if ((bool) config('security.headers.hsts.preload')) {
                $value .= '; preload';
            }

            $headers['Strict-Transport-Security'] = $value;
        }

        return $headers;
    }

    /**
     * Build the nonce-based CSP. Production stays strict; local development
     * relaxes just enough for the Vite HMR client (eval-based refresh, inlined
     * dev styles, and the HMR websocket).
     */
    private function contentSecurityPolicy(string $nonce): string
    {
        $isLocal = app()->environment('local');

        $scriptSrc = ["'self'", "'nonce-{$nonce}'", ...(array) config('security.headers.csp.script_src', [])];
        $styleSrc = ["'self'", "'nonce-{$nonce}'"];
        $connectSrc = ["'self'", ...(array) config('security.headers.csp.connect_src', [])];
        $imgSrc = ["'self'", ...(array) config('security.headers.csp.img_src', [])];
        $fontSrc = ["'self'", ...(array) config('security.headers.csp.font_src', [])];

        if ($isLocal) {
            $scriptSrc[] = "'unsafe-eval'";
            $styleSrc[] = "'unsafe-inline'";
            $connectSrc[] = 'ws:';
            $connectSrc[] = 'wss:';
        }

        $directives = [
            'default-src' => ["'self'"],
            'base-uri' => ["'self'"],
            'object-src' => ["'none'"],
            'frame-ancestors' => ["'none'"],
            'form-action' => ["'self'"],
            'img-src' => $imgSrc,
            'font-src' => $fontSrc,
            'script-src' => $scriptSrc,
            'style-src' => $styleSrc,
            // Radix/shadcn overlays and Framer Motion write inline style
            // ATTRIBUTES (style="..."), which nonces cannot authorize. Scope
            // the allowance to attributes only — never to the main style-src.
            'style-src-attr' => ["'unsafe-inline'"],
            'connect-src' => $connectSrc,
        ];

        return collect($directives)
            ->map(static fn (array $sources, string $name): string => $name.' '.implode(' ', $sources))
            ->implode('; ');
    }
}

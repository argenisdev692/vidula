<?php

declare(strict_types=1);

namespace Src\Middleware;

use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel as User;
use Modules\Notifications\Infrastructure\Notifications\SuspiciousLoginAttemptNotification;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * AuthRateLimitMiddleware
 *
 * Robust rate limiter for authentication routes.
 *
 * ── Behavior ──────────────────────────────────────────────
 * • Limits: 5 POST requests per minute per IP + identifier.
 * • On limit exceeded: returns 429 with retry-after header.
 * • On auth routes: when rate limit is exceeded, sends a
 *   queued notification (via Horizon) to the target email
 *   warning them that someone is attempting to access
 *   their account.
 *
 * ── Alert Throttle ────────────────────────────────────────
 * • Alert emails are throttled to 1 per 15 minutes per
 *   target email to prevent inbox flooding.
 */
final class AuthRateLimitMiddleware
{
    /**
     * Maximum POST attempts allowed.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Decay period in seconds (1 minute).
     */
    private const DECAY_SECONDS = 60;

    /**
     * Cooldown between alert emails in seconds (15 minutes).
     */
    private const ALERT_COOLDOWN_SECONDS = 900;

    public function handle(Request $request, Closure $next): Response
    {
        // Only rate-limit POST requests (form submissions)
        if ($request->method() !== 'POST') {
            return $next($request);
        }

        $throttleKey = $this->resolveThrottleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $this->handleExceeded($request, $throttleKey);

            $retryAfter = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => 'Too many requests. Please try again in ' . $retryAfter . ' seconds.',
                'retry_after' => $retryAfter,
            ], Response::HTTP_TOO_MANY_REQUESTS)
                ->withHeaders([
                    'Retry-After' => (string) $retryAfter,
                    'X-RateLimit-Limit' => (string) self::MAX_ATTEMPTS,
                    'X-RateLimit-Remaining' => '0',
                ]);
        }

        RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

        /** @var Response $response */
        $response = $next($request);

        // Attach rate limit headers to response
        $remaining = RateLimiter::remaining($throttleKey, self::MAX_ATTEMPTS);

        $response->headers->set('X-RateLimit-Limit', (string) self::MAX_ATTEMPTS);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $remaining));

        return $response;
    }

    /**
     * Build a unique throttle key from IP + extracted identifier.
     */
    private function resolveThrottleKey(Request $request): string
    {
        $identifier = $this->extractIdentifier($request);

        return 'auth-rate:' . Str::transliterate(
            Str::lower($identifier) . '|' . $request->ip(),
        );
    }

    /**
     * Extract the user identifier from the request body.
     * Checks common field names used across auth forms.
     */
    private function extractIdentifier(Request $request): string
    {
        return (string) (
            $request->input('email')
            ?? $request->input('identifier')
            ?? $request->input('username')
            ?? $request->ip()
        );
    }

    /**
     * Handle rate limit exceeded:
     * 1. Log the event with structured data
     * 2. Send alert notification to target email (throttled)
     */
    private function handleExceeded(Request $request, string $throttleKey): void
    {
        $identifier = $this->extractIdentifier($request);
        $ip = $request->ip() ?? 'unknown';
        $userAgent = $request->userAgent() ?? 'unknown';
        $route = $request->path();

        // ── Structured log ──
        Log::warning('Auth rate limit exceeded', [
            'identifier' => $identifier,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'route' => $route,
            'throttle_key' => $throttleKey,
            'timestamp' => now()->toIso8601String(),
        ]);

        // ── Send alert notification (throttled to 1 per 15 min) ──
        $alertCacheKey = 'auth-alert-sent:' . Str::lower($identifier);

        if (Cache::has($alertCacheKey)) {
            return; // Already alerted recently — don't flood inbox
        }

        // Find user by email or phone
        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if ($user === null) {
            return; // No account found — nothing to alert
        }

        // ── Dispatch queued notification via Horizon ──
        $user->notify(new SuspiciousLoginAttemptNotification(
            ipAddress: $ip,
            userAgent: $userAgent,
            attemptedAt: now()->toDateTimeString(),
            route: $route,
        ));

        // Mark alert as sent (15min cooldown)
        Cache::put($alertCacheKey, true, self::ALERT_COOLDOWN_SECONDS);

        Log::info('Suspicious login alert dispatched', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $ip,
        ]);
    }
}

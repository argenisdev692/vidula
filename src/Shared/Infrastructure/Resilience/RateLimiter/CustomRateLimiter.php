<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Resilience\RateLimiter;

use Illuminate\Support\Facades\RateLimiter;
use Shared\Domain\Exceptions\UnauthorizedException;

final class CustomRateLimiter
{
    /**
     * @param string $key
     * @param int $maxAttempts
     * @param int $decaySeconds
     * @param callable $action
     * @return mixed
     * @throws UnauthorizedException
     */
    public function execute(string $key, int $maxAttempts, int $decaySeconds, callable $action): mixed
    {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw new UnauthorizedException("Too many attempts. Please try again in " . RateLimiter::availableIn($key) . " seconds.");
        }

        try {
            $result = $action();
            RateLimiter::clear($key);
            return $result;
        } catch (\Exception $e) {
            RateLimiter::hit($key, $decaySeconds);
            throw $e;
        }
    }
}

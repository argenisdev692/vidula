<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Resilience\CircuitBreaker;

use Illuminate\Support\Facades\Cache;

final class RedisCircuitBreaker implements CircuitBreakerInterface
{
    private const FAILURE_THRESHOLD = 5;
    private const RESET_TIMEOUT = 60; // seconds

    public function execute(string $serviceName, callable $action, callable $fallback): mixed
    {
        $statusKey = "circuit_breaker:{$serviceName}:status";
        $failureKey = "circuit_breaker:{$serviceName}:failures";

        if (Cache::get($statusKey) === 'OPEN') {
            return $fallback();
        }

        try {
            $result = $action();
            Cache::forget($failureKey);
            return $result;
        } catch (\Exception $e) {
            $failures = (int) Cache::get($failureKey, 0) + 1;
            Cache::put($failureKey, $failures, self::RESET_TIMEOUT);

            if ($failures >= self::FAILURE_THRESHOLD) {
                Cache::put($statusKey, 'OPEN', self::RESET_TIMEOUT);
            }

            return $fallback();
        }
    }
}

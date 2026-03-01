<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Resilience\CircuitBreaker;

interface CircuitBreakerInterface
{
    /**
     * @param string $serviceName
     * @param callable $fallback
     * @param callable $action
     * @return mixed
     */
    public function execute(string $serviceName, callable $action, callable $fallback): mixed;
}

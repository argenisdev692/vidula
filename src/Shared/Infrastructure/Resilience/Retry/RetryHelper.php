<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Resilience\Retry;

final class RetryHelper
{
    /**
     * @param int $maxAttempts
     * @param int $delayMilliseconds
     * @param callable $action
     * @return mixed
     * @throws \Exception
     */
    public static function execute(int $maxAttempts, int $delayMilliseconds, callable $action): mixed
    {
        $attempts = 0;
        while ($attempts < $maxAttempts) {
            try {
                return $action();
            } catch (\Exception $e) {
                $attempts++;
                if ($attempts >= $maxAttempts) {
                    throw $e;
                }
                usleep($delayMilliseconds * 1000);
            }
        }

        throw new \Shared\Domain\Exceptions\IntegrationException("Maximum retry attempts reached.");
    }
}

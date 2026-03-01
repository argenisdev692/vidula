<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Observability\HealthCheck;

final class HealthCheckAggregator
{
    /** @var HealthCheckInterface[] */
    private array $checks = [];

    public function addCheck(HealthCheckInterface $check): void
    {
        $this->checks[] = $check;
    }

    public function aggregate(): array
    {
        $results = [];
        $overallStatus = 'UP';

        foreach ($this->checks as $check) {
            $result = $check->check();
            $results[] = $result;
            if ($result['status'] === 'DOWN') {
                $overallStatus = 'DOWN';
            }
        }

        return [
            'status' => $overallStatus,
            'timestamp' => now()->toIso8601String(),
            'checks' => $results
        ];
    }
}

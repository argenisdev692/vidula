<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Observability\HealthCheck;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class HealthCheckController extends Controller
{
    public function __construct(
        private readonly HealthCheckAggregator $aggregator
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $report = $this->aggregator->aggregate();
        $status = $report['status'] === 'UP' ? 200 : 503;

        return new JsonResponse($report, $status);
    }
}

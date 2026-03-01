<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Observability\HealthCheck;

use Illuminate\Support\Facades\DB;

final class DatabaseHealthCheck implements HealthCheckInterface
{
    public function check(): array
    {
        try {
            DB::connection()->getPdo();
            return [
                'status' => 'UP',
                'message' => 'Database connection established.',
                'component' => 'Database'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'DOWN',
                'message' => $e->getMessage(),
                'component' => 'Database'
            ];
        }
    }
}

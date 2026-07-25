<?php

declare(strict_types=1);

namespace Modules\VideoExport\Application\Queries;

use Modules\VideoExport\Application\Services\VideoExportJobStore;

final readonly class GetJobStatusHandler
{
    public function __construct(private VideoExportJobStore $jobs) {}

    /**
     * @return array{job_uuid: string, status: string, result: array<string, mixed>|null, error: string|null}
     */
    public function handle(string $jobUuid, int|string $userId): array
    {
        $payload = $this->jobs->get($jobUuid);
        if ($payload === null || (string) $payload['user_id'] !== (string) $userId) {
            return [
                'job_uuid' => $jobUuid,
                'status' => 'not_found',
                'result' => null,
                'error' => null,
            ];
        }

        return [
            'job_uuid' => $payload['job_uuid'],
            'status' => $payload['status'],
            'result' => $payload['result'],
            'error' => $payload['error'],
        ];
    }
}

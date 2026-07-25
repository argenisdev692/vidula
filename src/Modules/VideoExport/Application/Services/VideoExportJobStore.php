<?php

declare(strict_types=1);

namespace Modules\VideoExport\Application\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Ephemeral job status store (Redis Cache). No DB tables.
 *
 * @phpstan-type JobPayload array{
 *     job_uuid: string,
 *     user_id: int|string,
 *     status: string,
 *     mode: string,
 *     result: array<string, mixed>|null,
 *     error: string|null,
 *     updated_at: string
 * }
 */
final readonly class VideoExportJobStore
{
    public function __construct(private CacheRepository $cache) {}

    private function key(string $jobUuid): string
    {
        return 'video_export:job:'.$jobUuid;
    }

    /**
     * @param  JobPayload  $payload
     */
    public function put(array $payload, ?int $ttlSeconds = null): void
    {
        $ttl = $ttlSeconds ?? (int) config('video-export.job_cache_ttl_seconds', 86400);
        $this->cache->put($this->key($payload['job_uuid']), $payload, $ttl);
    }

    /**
     * @return JobPayload|null
     */
    public function get(string $jobUuid): ?array
    {
        /** @var JobPayload|null $payload */
        $payload = $this->cache->get($this->key($jobUuid));

        return is_array($payload) ? $payload : null;
    }

    public function exists(string $jobUuid): bool
    {
        return $this->cache->has($this->key($jobUuid));
    }

    public function markFailed(string $jobUuid, string $error): void
    {
        $current = $this->get($jobUuid);
        if ($current === null) {
            return;
        }
        $current['status'] = 'failed';
        $current['error'] = $error;
        $current['updated_at'] = now()->toIso8601String();
        $this->put($current, (int) config('video-export.job_failed_cache_ttl_seconds', 604800));
    }
}

<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Ports;

use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\JobSearchConfigEloquentModel;

interface JobSearchConfigRepositoryPort
{
    public function findByUuid(string $uuid): ?JobSearchConfigEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): JobSearchConfigEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(JobSearchConfigEloquentModel $config, array $attributes): JobSearchConfigEloquentModel;

    /**
     * @return list<JobSearchConfigEloquentModel>
     */
    public function findScheduledEnabled(): array;

    /**
     * Recent configs for the studio index sidebar/select.
     *
     * @return list<JobSearchConfigEloquentModel>
     */
    public function recentForListing(int $limit = 20): array;

    /**
     * @param  array<int, string>  $uuids
     */
    public function bulkSoftDeleteByUuid(array $uuids): int;

    /**
     * @param  array<int, string>  $uuids
     */
    public function bulkRestoreByUuid(array $uuids): int;
}

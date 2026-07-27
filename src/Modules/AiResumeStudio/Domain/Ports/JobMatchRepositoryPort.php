<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Ports;

use Illuminate\Support\LazyCollection;
use Modules\AiResumeStudio\Application\DTOs\StudioFilterData;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\JobMatchEloquentModel;

interface JobMatchRepositoryPort
{
    public function findByUuid(string $uuid): ?JobMatchEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): JobMatchEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(JobMatchEloquentModel $match, array $attributes): JobMatchEloquentModel;

    /**
     * Insert or touch last_seen_at when the canonical URL already exists for the user.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function upsertByCanonicalUrl(int $userId, string $canonicalUrl, array $attributes): JobMatchEloquentModel;

    /**
     * @return LazyCollection<int, JobMatchEloquentModel>
     */
    public function lazyForExport(StudioFilterData $filters): LazyCollection;

    /**
     * @param  array<int, string>  $uuids
     */
    public function bulkSoftDeleteByUuid(array $uuids): int;

    /**
     * @param  array<int, string>  $uuids
     */
    public function bulkRestoreByUuid(array $uuids): int;
}

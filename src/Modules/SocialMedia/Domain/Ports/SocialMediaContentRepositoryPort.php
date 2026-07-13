<?php

declare(strict_types=1);

namespace Modules\SocialMedia\Domain\Ports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\SocialMedia\Application\DTOs\SocialMediaContentFilterData;
use Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\SocialMediaContentEloquentModel;

interface SocialMediaContentRepositoryPort
{
    /**
     * @return LengthAwarePaginator<int, SocialMediaContentEloquentModel>
     */
    public function paginate(SocialMediaContentFilterData $filters, int $perPage): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?SocialMediaContentEloquentModel;

    /**
     * Scheduled content packages whose `scheduled_at` has been reached (cron
     * auto-publish).
     *
     * @return Collection<int, SocialMediaContentEloquentModel>
     */
    public function dueForScheduledPublishing(): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): SocialMediaContentEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(SocialMediaContentEloquentModel $content, array $attributes): SocialMediaContentEloquentModel;

    public function softDelete(string $uuid): bool;

    public function restore(string $uuid): bool;

    /**
     * @param  array<int, string>  $uuids
     */
    public function bulkSoftDeleteByUuid(array $uuids): int;

    /**
     * @param  array<int, string>  $uuids
     */
    public function bulkRestoreByUuid(array $uuids): int;
}

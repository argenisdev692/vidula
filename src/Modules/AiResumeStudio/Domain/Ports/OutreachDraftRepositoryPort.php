<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Ports;

use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\OutreachDraftEloquentModel;

interface OutreachDraftRepositoryPort
{
    public function findByUuid(string $uuid): ?OutreachDraftEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): OutreachDraftEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(OutreachDraftEloquentModel $draft, array $attributes): OutreachDraftEloquentModel;

    /**
     * @param  array<int, string>  $uuids
     */
    public function bulkSoftDeleteByUuid(array $uuids): int;

    /**
     * @param  array<int, string>  $uuids
     */
    public function bulkRestoreByUuid(array $uuids): int;
}

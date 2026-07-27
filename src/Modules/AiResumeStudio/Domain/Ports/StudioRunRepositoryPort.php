<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Ports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\AiResumeStudio\Application\DTOs\StudioFilterData;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\StudioRunEloquentModel;

interface StudioRunRepositoryPort
{
    public function paginate(StudioFilterData $filters, int $perPage): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?StudioRunEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): StudioRunEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(StudioRunEloquentModel $run, array $attributes): StudioRunEloquentModel;
}

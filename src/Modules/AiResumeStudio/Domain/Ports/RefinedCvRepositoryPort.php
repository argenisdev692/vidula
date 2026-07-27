<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Ports;

use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\RefinedCvEloquentModel;

interface RefinedCvRepositoryPort
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): RefinedCvEloquentModel;

    public function findByUuid(string $uuid): ?RefinedCvEloquentModel;

    public function nextVersionForCv(int $cvId): int;
}

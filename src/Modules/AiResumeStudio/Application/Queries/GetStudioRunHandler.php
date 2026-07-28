<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Application\Queries;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\AiResumeStudio\Domain\Ports\StudioRunRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\StudioRunEloquentModel;

final readonly class GetStudioRunHandler
{
    public function __construct(private StudioRunRepositoryPort $runs) {}

    public function handle(string $uuid, int $userId): StudioRunEloquentModel
    {
        $run = $this->runs->findByUuid($uuid)
          ?? throw (new ModelNotFoundException)->setModel(StudioRunEloquentModel::class, [$uuid]);

        if ((int) $run->user_id !== $userId) {
            throw (new ModelNotFoundException)->setModel(StudioRunEloquentModel::class, [$uuid]);
        }

        return $run;
    }
}

<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Application\Commands;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\AiResumeStudio\Application\DTOs\UpdateJobMatchData;
use Modules\AiResumeStudio\Domain\Ports\JobMatchRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\JobMatchEloquentModel;

final readonly class UpdateJobMatchHandler
{
    public function __construct(private JobMatchRepositoryPort $matches) {}

    public function handle(string $uuid, UpdateJobMatchData $data): JobMatchEloquentModel
    {
        $match = $this->matches->findByUuid($uuid)
          ?? throw (new ModelNotFoundException)->setModel(JobMatchEloquentModel::class, [$uuid]);

        return $this->matches->update($match, [
            'application_status' => $data->applicationStatus,
        ]);
    }
}

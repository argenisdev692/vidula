<?php

declare(strict_types=1);

namespace Modules\Cvs\Application\Queries;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Cvs\Domain\Ports\CvRepositoryPort;
use Modules\Cvs\Infrastructure\Persistence\Eloquent\Models\CvEloquentModel;

final readonly class GetCvHandler
{
    public function __construct(private CvRepositoryPort $cvs) {}

    public function handle(string $uuid): CvEloquentModel
    {
        return $this->cvs->findByUuid($uuid)
            ?? throw (new ModelNotFoundException)->setModel(CvEloquentModel::class, [$uuid]);
    }
}

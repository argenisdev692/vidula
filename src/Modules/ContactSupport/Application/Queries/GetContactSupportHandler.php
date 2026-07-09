<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Application\Queries;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\ContactSupport\Domain\Ports\ContactSupportRepositoryPort;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;

final readonly class GetContactSupportHandler
{
    public function __construct(private ContactSupportRepositoryPort $contactSupports) {}

    public function handle(string $uuid): ContactSupportEloquentModel
    {
        return $this->contactSupports->findByUuid($uuid)
            ?? throw (new ModelNotFoundException)->setModel(ContactSupportEloquentModel::class, [$uuid]);
    }
}

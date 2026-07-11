<?php

declare(strict_types=1);

namespace Modules\Services\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Services\Application\DTOs\ServiceData;
use Modules\Services\Domain\Ports\ServiceRepositoryPort;
use Modules\Services\Infrastructure\Cache\ServicePublicFeedCache;
use Modules\Services\Infrastructure\Persistence\Eloquent\Models\ServiceEloquentModel;

/**
 * Persists a new service. Authorization (permission:CREATE_SERVICES) is
 * enforced at the route — never inside this handler.
 */
final readonly class CreateServiceHandler
{
    public function __construct(private ServiceRepositoryPort $services) {}

    #[\NoDiscard]
    public function handle(ServiceData $data, int $userId): ServiceEloquentModel
    {
        $service = DB::transaction(fn () => $this->services->create([
            'name' => $data->name,
            'slug' => $data->slug,
            'description' => $data->description,
            'is_active' => $data->isActive,
            'sort_order' => $data->sortOrder,
            'user_id' => $userId,
        ]));

        ServicePublicFeedCache::flush();

        return $service;
    }
}

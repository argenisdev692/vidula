<?php

declare(strict_types=1);

namespace Modules\Services\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Services\Application\DTOs\ServiceData;
use Modules\Services\Domain\Ports\ServiceRepositoryPort;
use Modules\Services\Infrastructure\Cache\ServicePublicFeedCache;
use Modules\Services\Infrastructure\Persistence\Eloquent\Models\ServiceEloquentModel;

/**
 * Updates an existing service. Authorization (permission:UPDATE_SERVICES) is
 * enforced at the route.
 */
final readonly class UpdateServiceHandler
{
    public function __construct(private ServiceRepositoryPort $services) {}

    public function handle(ServiceEloquentModel $service, ServiceData $data): ServiceEloquentModel
    {
        $updated = DB::transaction(fn () => $this->services->update($service, [
            'name' => $data->name,
            'slug' => $data->slug,
            'description' => $data->description,
            'is_active' => $data->isActive,
            'sort_order' => $data->sortOrder,
        ]));

        ServicePublicFeedCache::flush();

        return $updated;
    }
}

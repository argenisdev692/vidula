<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Soft-deletes a set of users by UUID. Authorization (permission:BULK_DELETE_USERS)
 * is enforced at the route — never inside this handler (no god-handler).
 */
final readonly class BulkDeleteUsersHandler
{
    public function __construct(private UserRepositoryPort $users) {}

    public function handle(BulkUuidsData $data): int
    {
        return DB::transaction(fn () => $this->users->bulkSoftDeleteByUuid($data->uuids));
    }
}

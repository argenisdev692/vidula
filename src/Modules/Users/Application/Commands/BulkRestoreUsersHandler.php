<?php

declare(strict_types=1);

namespace Modules\Users\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Restores a set of soft-deleted users by UUID. Paired with
 * {@see BulkDeleteUsersHandler}. Authorization (permission:BULK_RESTORE_USERS)
 * is enforced at the route.
 */
final readonly class BulkRestoreUsersHandler
{
    public function __construct(private UserRepositoryPort $users) {}

    public function handle(BulkUuidsData $data): int
    {
        return DB::transaction(fn () => $this->users->bulkRestoreByUuid($data->uuids));
    }
}

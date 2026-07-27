<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\AiResumeStudio\Domain\Ports\JobMatchRepositoryPort;
use Shared\Application\DTOs\BulkUuidsData;

final readonly class BulkDeleteJobMatchesHandler
{
    public function __construct(private JobMatchRepositoryPort $matches) {}

    public function handle(BulkUuidsData $data): int
    {
        return DB::transaction(fn (): int => $this->matches->bulkSoftDeleteByUuid($data->uuids));
    }
}

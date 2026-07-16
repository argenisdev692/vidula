<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Meeting\Application\DTOs\MeetingFilterData;
use Modules\Meeting\Domain\Ports\MeetingRepositoryPort;

final readonly class ListMeetingsHandler
{
    public function __construct(private MeetingRepositoryPort $meetings) {}

    public function handle(MeetingFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->meetings->paginate($filters, $perPage);
    }
}

<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\ContactSupport\Application\DTOs\ContactSupportFilterData;
use Modules\ContactSupport\Domain\Ports\ContactSupportRepositoryPort;

final readonly class ListContactSupportsHandler
{
    public function __construct(private ContactSupportRepositoryPort $contactSupports) {}

    public function handle(ContactSupportFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->contactSupports->paginate($filters, $perPage);
    }
}

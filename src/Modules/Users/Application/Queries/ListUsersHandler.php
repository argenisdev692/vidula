<?php

declare(strict_types=1);

namespace Modules\Users\Application\Queries;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Users\Application\DTOs\UserFilterData;
use Modules\Users\Domain\Ports\UserRepositoryPort;

final readonly class ListUsersHandler
{
    public function __construct(private UserRepositoryPort $users) {}

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function handle(UserFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->users->paginate($filters, $perPage);
    }
}

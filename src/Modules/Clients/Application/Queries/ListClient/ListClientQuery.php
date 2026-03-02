<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Queries\ListClient;

use Modules\Clients\Application\DTOs\ClientFilterDTO;

final readonly class ListClientQuery
{
    public function __construct(
        public ClientFilterDTO $filters
    ) {
    }
}

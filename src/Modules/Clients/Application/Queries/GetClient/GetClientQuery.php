<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Queries\GetClient;

final readonly class GetClientQuery
{
    public function __construct(
        public string $uuid,
        public bool $isUserUuid = false
    ) {
    }
}

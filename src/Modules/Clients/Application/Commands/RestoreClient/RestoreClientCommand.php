<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Commands\RestoreClient;

final readonly class RestoreClientCommand
{
    public function __construct(
        public string $id
    ) {
    }
}

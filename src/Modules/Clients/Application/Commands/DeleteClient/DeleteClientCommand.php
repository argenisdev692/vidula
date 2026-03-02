<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Commands\DeleteClient;

final readonly class DeleteClientCommand
{
    public function __construct(
        public string $id
    ) {
    }
}

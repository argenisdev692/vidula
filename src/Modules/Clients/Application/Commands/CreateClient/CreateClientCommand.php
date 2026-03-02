<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Commands\CreateClient;

use Modules\Clients\Application\DTOs\CreateClientDTO;

final readonly class CreateClientCommand
{
    public function __construct(
        public CreateClientDTO $dto
    ) {
    }
}

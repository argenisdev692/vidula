<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Commands\UpdateClient;

use Modules\Clients\Application\DTOs\UpdateClientDTO;

final readonly class UpdateClientCommand
{
    public function __construct(
        public string $id,
        public UpdateClientDTO $dto
    ) {
    }
}

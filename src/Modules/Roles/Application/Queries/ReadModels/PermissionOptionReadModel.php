<?php

declare(strict_types=1);

namespace Modules\Roles\Application\Queries\ReadModels;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
final class PermissionOptionReadModel extends Data
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $guardName,
    ) {
    }
}

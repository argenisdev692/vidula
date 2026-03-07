<?php

declare(strict_types=1);

namespace Modules\Permissions\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class CreatePermissionDTO extends Data
{
    public function __construct(
        public string $name,
        public string $guardName = 'web',
        public array $roles = [],
    ) {
    }
}

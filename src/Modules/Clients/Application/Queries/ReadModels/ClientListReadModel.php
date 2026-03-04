<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Queries\ReadModels;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
final class ClientListReadModel extends Data
{
    public function __construct(
        public string $id,
        public string $clientName,
        public ?string $email,
        public ?string $phone,
        public ?string $createdAt,
        public ?string $deletedAt
    ) {
    }
}
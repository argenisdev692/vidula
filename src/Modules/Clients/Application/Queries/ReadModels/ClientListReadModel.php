<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Queries\ReadModels;

use Spatie\LaravelData\Data;

final class ClientListReadModel extends Data
{
    public function __construct(
        public string $id,
        public string $companyName,
        public ?string $email,
        public ?string $phone,
        public ?string $createdAt,
        public ?string $deletedAt
    ) {
    }
}
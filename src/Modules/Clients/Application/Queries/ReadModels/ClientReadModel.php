<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Queries\ReadModels;

use Spatie\LaravelData\Data;

final class ClientReadModel extends Data
{
    public function __construct(
        public string $uuid,
        public string $userUuid,
        public string $companyName,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        public array $socialLinks,
        public array $coordinates,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $deletedAt = null
    ) {
    }
}
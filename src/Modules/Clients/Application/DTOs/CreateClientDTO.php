<?php

declare(strict_types=1);

namespace Modules\Clients\Application\DTOs;

use Spatie\LaravelData\Data;

final class CreateClientDTO extends Data
{
    public function __construct(
        public string $userUuid,
        public string $companyName,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $website = null,
        public ?string $facebookLink = null,
        public ?string $instagramLink = null,
        public ?string $linkedinLink = null,
        public ?string $twitterLink = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {
    }
}
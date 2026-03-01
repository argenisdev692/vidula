<?php

declare(strict_types=1);

namespace Modules\CompanyData\Domain\ValueObjects;

final readonly class Coordinates
{
    public function __construct(
        public ?float $latitude,
        public ?float $longitude
    ) {
    }

    public function hasValues(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}

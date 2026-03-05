<?php

declare(strict_types=1);

namespace Modules\CompanyData\Domain\ValueObjects;

final class Coordinates
{
    public function __construct(
        public private(set) ?float $latitude {
            set {
                if($value !== null && ($value < -90.0 || $value > 90.0)) {
                    throw new \InvalidArgumentException("Latitude must be between -90 and 90, got: {$value}");
                }
                $this->latitude = $value;
            }
        },
        public private(set) ?float $longitude {
            set {
                if($value !== null && ($value < -180.0 || $value > 180.0)) {
                    throw new \InvalidArgumentException("Longitude must be between -180 and 180, got: {$value}");
                }
                $this->longitude = $value;
            }
        },
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

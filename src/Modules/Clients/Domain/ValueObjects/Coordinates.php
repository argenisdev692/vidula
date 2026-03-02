<?php

declare(strict_types=1);

namespace Modules\Clients\Domain\ValueObjects;

/**
 * Coordinates Value Object
 * 
 * Property Hooks were proposed but NOT included in PHP 8.5 final release.
 * Using constructor validation instead.
 */
final readonly class Coordinates
{
    public ?float $latitude;
    public ?float $longitude;

    public function __construct(?float $latitude, ?float $longitude)
    {
        if ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
            throw new \InvalidArgumentException('Latitude must be between -90 and 90');
        }
        
        if ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
            throw new \InvalidArgumentException('Longitude must be between -180 and 180');
        }
        
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    #[\NoDiscard]
    public function hasValues(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    #[\NoDiscard]
    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}


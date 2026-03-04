<?php

declare(strict_types=1);

namespace Modules\Clients\Application\DTOs;

use Spatie\LaravelData\Data;

/**
 * @OA\Schema(
 *     schema="UpdateClientDTO",
 *     @OA\Property(property="clientName", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="email", type="string", format="email", nullable=true),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="nif", type="string", nullable=true),
 *     @OA\Property(property="website", type="string", format="url", nullable=true),
 *     @OA\Property(property="facebookLink", type="string", format="url", nullable=true),
 *     @OA\Property(property="instagramLink", type="string", format="url", nullable=true),
 *     @OA\Property(property="linkedinLink", type="string", format="url", nullable=true),
 *     @OA\Property(property="twitterLink", type="string", format="url", nullable=true),
 *     @OA\Property(property="latitude", type="number", format="float", nullable=true),
 *     @OA\Property(property="longitude", type="number", format="float", nullable=true),
 * )
 */
final class UpdateClientDTO extends Data
{
    public function __construct(
        public ?string $clientName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $nif = null,
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
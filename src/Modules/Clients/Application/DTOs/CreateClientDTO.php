<?php

declare(strict_types=1);

namespace Modules\Clients\Application\DTOs;

use Spatie\LaravelData\Data;

/**
 * @OA\Schema(
 *     schema="CreateClientDTO",
 *     required={"userUuid", "clientName"},
 *     @OA\Property(property="userUuid", type="string", format="uuid"),
 *     @OA\Property(property="clientName", type="string", maxLength=255),
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
final class CreateClientDTO extends Data
{
    public function __construct(
        public string $userUuid,
        public string $clientName,
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
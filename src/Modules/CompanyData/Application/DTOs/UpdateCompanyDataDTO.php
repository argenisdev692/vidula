<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * @OA\Schema(
 *     schema="UpdateCompanyDataDTO",
 *     type="object",
 *     required={"company_name"},
 *     @OA\Property(property="company_name", type="string", maxLength=255),
 *     @OA\Property(property="email", type="string", format="email", nullable=true),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="website", type="string", format="uri", nullable=true),
 *     @OA\Property(property="facebook", type="string", format="uri", nullable=true),
 *     @OA\Property(property="instagram", type="string", format="uri", nullable=true),
 *     @OA\Property(property="linkedin", type="string", format="uri", nullable=true),
 *     @OA\Property(property="twitter", type="string", format="uri", nullable=true),
 *     @OA\Property(property="latitude", type="number", format="float", nullable=true),
 *     @OA\Property(property="longitude", type="number", format="float", nullable=true),
 * )
 */
#[MapInputName(SnakeCaseMapper::class)]
final class UpdateCompanyDataDTO extends Data
{
    public function __construct(
        public string $companyName,
        public ?string $name = null,
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
        public ?string $signaturePath = null,
    ) {
    }
}

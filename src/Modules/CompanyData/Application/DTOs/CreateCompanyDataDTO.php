<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * @OA\Schema(
 *     schema="CreateCompanyDataDTO",
 *     type="object",
 *     required={"user_uuid", "company_name"},
 *     @OA\Property(property="user_uuid", type="string", format="uuid"),
 *     @OA\Property(property="company_name", type="string", maxLength=255),
 *     @OA\Property(property="email", type="string", format="email", nullable=true),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="address", type="string", nullable=true),
 * )
 */
#[MapInputName(SnakeCaseMapper::class)]
final class CreateCompanyDataDTO extends Data
{
    public function __construct(
        public string $userUuid,
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

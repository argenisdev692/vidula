<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Queries\ReadModels;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * CompanyDataReadModel
 *
 * @OA\Schema(
 *     schema="CompanyDataReadModel",
 *     type="object",
 *     required={"uuid", "userUuid", "companyName", "status"},
 *     @OA\Property(property="uuid", type="string", format="uuid"),
 *     @OA\Property(property="user_uuid", type="string", format="uuid"),
 *     @OA\Property(property="company_name", type="string"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="social_links", type="object", nullable=true),
 *     @OA\Property(property="coordinates", type="object", nullable=true),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "pending"}),
 *     @OA\Property(property="signature_url", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true),
 * )
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class CompanyDataReadModel extends Data
{
    public function __construct(
        public string $uuid,
        public string $userUuid,
        public string $companyName,
        public ?string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        public ?string $website,
        public ?string $facebookLink,
        public ?string $instagramLink,
        public ?string $linkedinLink,
        public ?string $twitterLink,
        public array $socialLinks,
        public array $coordinates,
        public ?float $latitude,
        public ?float $longitude,
        public string $status,
        public ?string $signatureUrl = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $deletedAt = null,
    ) {
    }
}

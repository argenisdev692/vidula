<?php
declare(strict_types=1);
namespace Modules\Clients\Domain\Entities;

use Modules\Clients\Domain\ValueObjects\ClientId;
use Modules\Clients\Domain\ValueObjects\UserId;
use Modules\Clients\Domain\ValueObjects\SocialLinks;
use Modules\Clients\Domain\ValueObjects\Coordinates;
use Modules\Clients\Domain\Enums\CompanyStatus;
use Shared\Domain\Entities\AggregateRoot;

/**
 * Client — Domain Entity (Aggregate Root)
 * 
 * Fully immutable entity using readonly properties.
 * Use clone with to create modified copies.
 */
final class Client extends AggregateRoot
{
    public function __construct(
        public readonly ClientId $id,
        public readonly UserId $userId,
        public readonly string $companyName,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $address = null,
        public readonly ?SocialLinks $socialLinks = null,
        public readonly ?Coordinates $coordinates = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?string $deletedAt = null
    ) {
    }

    public static function create(
        ClientId $id,
        UserId $userId,
        string $companyName,
        ?string $email = null,
        ?string $phone = null,
        ?string $address = null,
        ?SocialLinks $socialLinks = null,
        ?Coordinates $coordinates = null,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            companyName: $companyName,
            email: $email,
            phone: $phone,
            address: $address,
            socialLinks: $socialLinks ?? new SocialLinks(),
            coordinates: $coordinates ?? new Coordinates(null, null),
            createdAt: date('c'),
            updatedAt: date('c')
        );
    }

    /**
     * Update client information using clone with
     */
    public function update(
        ?string $companyName = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $address = null,
        ?SocialLinks $socialLinks = null,
        ?Coordinates $coordinates = null,
    ): self {
        return clone($this, [
            'companyName' => $companyName ?? $this->companyName,
            'email' => $email ?? $this->email,
            'phone' => $phone ?? $this->phone,
            'address' => $address ?? $this->address,
            'socialLinks' => $socialLinks ?? $this->socialLinks,
            'coordinates' => $coordinates ?? $this->coordinates,
            'updatedAt' => date('c'),
        ]);
    }

    /**
     * Soft delete the client
     */
    public function softDelete(): self
    {
        return clone($this, [
            'deletedAt' => date('c'),
            'updatedAt' => date('c'),
        ]);
    }

    /**
     * Restore a soft-deleted client
     */
    public function restore(): self
    {
        return clone($this, [
            'deletedAt' => null,
            'updatedAt' => date('c'),
        ]);
    }
}

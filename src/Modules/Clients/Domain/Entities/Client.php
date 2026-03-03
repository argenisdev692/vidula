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
        public ClientId $id,
        public UserId $userId,
        public string $companyName,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $nif = null,
        public ?SocialLinks $socialLinks = null,
        public ?Coordinates $coordinates = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $deletedAt = null
    ) {
    }

    public static function create(
        ClientId $id,
        UserId $userId,
        string $companyName,
        ?string $email = null,
        ?string $phone = null,
        ?string $address = null,
        ?string $nif = null,
        ?SocialLinks $socialLinks = null,
        ?Coordinates $coordinates = null,
    ): self {
        $client = new self(
            id: $id,
            userId: $userId,
            companyName: $companyName,
            email: $email,
            phone: $phone,
            address: $address,
            nif: $nif,
            socialLinks: $socialLinks ?? new SocialLinks(),
            coordinates: $coordinates ?? new Coordinates(null, null),
            createdAt: date('c'),
            updatedAt: date('c')
        );

        $client->recordDomainEvent(new \Modules\Clients\Domain\Events\ClientCreated(
            aggregateId: $id->value,
            companyName: $companyName,
            occurredOn: date('c')
        ));

        return $client;
    }

    /**
     * Update client information using clone with
     */
    public function update(
        ?string $companyName = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $address = null,
        ?string $nif = null,
        ?SocialLinks $socialLinks = null,
        ?Coordinates $coordinates = null
    ): self {
        $updated = clone $this;
        $updated->companyName = $companyName ?? $this->companyName;
        $updated->email = $email ?? $this->email;
        $updated->phone = $phone ?? $this->phone;
        $updated->address = $address ?? $this->address;
        $updated->nif = $nif ?? $this->nif;
        $updated->socialLinks = $socialLinks ?? $this->socialLinks;
        $updated->coordinates = $coordinates ?? $this->coordinates;
        $updated->updatedAt = date('c');

        $updated->recordDomainEvent(new \Modules\Clients\Domain\Events\ClientUpdated(
            aggregateId: $this->id->value,
            companyName: $updated->companyName,
            occurredOn: date('c')
        ));

        return $updated;
    }

    /**
     * Soft delete the client
     */
    public function softDelete(): self
    {
        $updated = clone $this;
        $updated->deletedAt = date('c');
        $updated->updatedAt = date('c');
        return $updated;
    }

    /**
     * Restore a soft-deleted client
     */
    public function restore(): self
    {
        $updated = clone $this;
        $updated->deletedAt = null;
        $updated->updatedAt = date('c');
        return $updated;
    }
}

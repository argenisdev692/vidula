<?php
declare(strict_types=1);
namespace Modules\Clients\Domain\Entities;

use Modules\Clients\Domain\ValueObjects\ClientId;
use Modules\Clients\Domain\ValueObjects\UserId;
use Modules\Clients\Domain\ValueObjects\SocialLinks;
use Modules\Clients\Domain\ValueObjects\Coordinates;
use Shared\Domain\Entities\AggregateRoot;

/**
 * Client — Domain Entity (Aggregate Root)
 *
 * Independent CRUD entity. No dependency on Company module.
 */
final class Client extends AggregateRoot
{
    public function __construct(
        public ClientId $id,
        public UserId $userId,
        public string $clientName,
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
        string $clientName,
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
            clientName: $clientName,
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
            clientName: $clientName,
            occurredOn: date('c')
        ));

        return $client;
    }

    /**
     * Update client information using clone with (PHP 8.5)
     */
    public function update(
        ?string $clientName = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $address = null,
        ?string $nif = null,
        ?SocialLinks $socialLinks = null,
        ?Coordinates $coordinates = null
    ): self {
        $updated = clone($this, [
            'clientName' => $clientName ?? $this->clientName,
            'email' => $email ?? $this->email,
            'phone' => $phone ?? $this->phone,
            'address' => $address ?? $this->address,
            'nif' => $nif ?? $this->nif,
            'socialLinks' => $socialLinks ?? $this->socialLinks,
            'coordinates' => $coordinates ?? $this->coordinates,
            'updatedAt' => date('c'),
        ]);

        $updated->recordDomainEvent(new \Modules\Clients\Domain\Events\ClientUpdated(
            aggregateId: $this->id->value,
            clientName: $updated->clientName,
            occurredOn: date('c')
        ));

        return $updated;
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

<?php

declare(strict_types=1);

namespace Modules\Clients\Tests\Unit\Domain;

use Modules\Clients\Domain\Entities\Client;
use Modules\Clients\Domain\ValueObjects\ClientId;
use Modules\Clients\Domain\ValueObjects\UserId;
use Modules\Clients\Domain\ValueObjects\SocialLinks;
use Modules\Clients\Domain\ValueObjects\Coordinates;
use PHPUnit\Framework\TestCase;

/**
 * ClientEntityTest — Domain invariants for the Client aggregate root.
 */
final class ClientEntityTest extends TestCase
{
    private function createClient(): Client
    {
        return Client::create(
            id: new ClientId('aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee'),
            userId: new UserId('11111111-2222-3333-4444-555555555555'),
            clientName: 'Acme Corp',
            email: 'contact@acme.com',
            phone: '+1234567890',
            address: '123 Main St',
            nif: 'B12345678',
        );
    }

    public function test_create_sets_all_fields(): void
    {
        $client = $this->createClient();

        $this->assertSame('aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee', $client->id->value);
        $this->assertSame('11111111-2222-3333-4444-555555555555', $client->userId->value);
        $this->assertSame('Acme Corp', $client->clientName);
        $this->assertSame('contact@acme.com', $client->email);
        $this->assertSame('+1234567890', $client->phone);
        $this->assertSame('123 Main St', $client->address);
        $this->assertSame('B12345678', $client->nif);
        $this->assertNotNull($client->createdAt);
        $this->assertNotNull($client->updatedAt);
        $this->assertNull($client->deletedAt);
    }

    public function test_create_records_domain_event(): void
    {
        $client = $this->createClient();
        $events = $client->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(\Modules\Clients\Domain\Events\ClientCreated::class, $events[0]);
    }

    public function test_update_returns_new_instance_with_changed_name(): void
    {
        $client = $this->createClient();
        // Clear events from create
        $client->pullDomainEvents();

        $updated = $client->update(clientName: 'New Corp Name');

        // Original unchanged (immutability)
        $this->assertSame('Acme Corp', $client->clientName);
        // Updated has new name
        $this->assertSame('New Corp Name', $updated->clientName);
        // Other fields preserved
        $this->assertSame('contact@acme.com', $updated->email);
    }

    public function test_update_records_client_updated_event(): void
    {
        $client = $this->createClient();
        $client->pullDomainEvents();

        $updated = $client->update(clientName: 'Changed');
        $events = $updated->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(\Modules\Clients\Domain\Events\ClientUpdated::class, $events[0]);
    }

    public function test_soft_delete_sets_deleted_at(): void
    {
        $client = $this->createClient();
        $deleted = $client->softDelete();

        $this->assertNotNull($deleted->deletedAt);
        // Original unchanged
        $this->assertNull($client->deletedAt);
    }

    public function test_restore_clears_deleted_at(): void
    {
        $client = $this->createClient();
        $deleted = $client->softDelete();
        $restored = $deleted->restore();

        $this->assertNull($restored->deletedAt);
        // Deleted instance still has deletedAt
        $this->assertNotNull($deleted->deletedAt);
    }

    public function test_coordinates_validates_latitude_range(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Coordinates(latitude: 91.0, longitude: 0.0);
    }

    public function test_coordinates_validates_longitude_range(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Coordinates(latitude: 0.0, longitude: 181.0);
    }

    public function test_social_links_value_object_stores_urls(): void
    {
        $links = new SocialLinks(
            facebook: 'https://facebook.com/test',
            website: 'https://example.com',
        );

        $this->assertSame('https://facebook.com/test', $links->facebook);
        $this->assertSame('https://example.com', $links->website);
        $this->assertNull($links->instagram);
    }
}

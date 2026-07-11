<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Honeypot\EncryptedTime;
use Tests\TestCase;

/**
 * Public REST API for the marketing landing page: POST /api/contact-supports
 * (submit) + GET /api/contact-supports/honeypot (trap descriptor). Stateless,
 * unauthenticated, JSON in/out.
 */
final class PublicContactApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ADA@Example.com',
            'phone' => '+15551234567',
            'subject' => 'Billing question',
            'message' => 'I need help understanding my last invoice, please.',
            'sms_consent' => true,
            ...$overrides,
        ];
    }

    public function test_the_honeypot_descriptor_is_publicly_available(): void
    {
        $this->getJson('/api/contact-supports/honeypot')
            ->assertOk()
            ->assertJsonStructure(['nameFieldName', 'validFromFieldName', 'encryptedValidFrom']);
    }

    public function test_a_landing_page_submission_is_stored_and_returns_201(): void
    {
        $this->postJson('/api/contact-supports', $this->payload())
            ->assertCreated()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('contact_supports', [
            'email' => 'ada@example.com',
            'is_spam' => false,
        ]);
    }

    public function test_validation_errors_return_422(): void
    {
        $this->postJson('/api/contact-supports', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'phone', 'subject', 'message']);
    }

    public function test_spammy_content_is_stored_but_flagged(): void
    {
        $this->postJson('/api/contact-supports', $this->payload([
            'subject' => 'Special offer',
            'message' => 'Cheap viagra and casino bonuses at http://a.co and http://b.co now.',
        ]))->assertCreated();

        $this->assertDatabaseHas('contact_supports', [
            'email' => 'ada@example.com',
            'is_spam' => true,
        ]);
    }

    public function test_a_filled_honeypot_is_dropped_silently_with_the_same_201(): void
    {
        $this->postJson('/api/contact-supports', $this->payload([
            'my_name' => 'http://spam.example',
            'valid_from' => EncryptedTime::create(now()->subMinute()),
        ]))->assertCreated();

        // Bot learns nothing (identical response) but nothing is persisted.
        $this->assertDatabaseCount('contact_supports', 0);
    }
}

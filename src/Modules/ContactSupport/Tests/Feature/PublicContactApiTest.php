<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Honeypot\EncryptedTime;
use Tests\TestCase;

/**
 * CRM-token-gated REST API for the marketing landing page:
 * POST /api/contact-supports/public + GET /api/contact-supports/public/honeypot.
 * Stateless JSON; Astro must send Authorization: Bearer {CRM_API_TOKEN}.
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

    public function test_without_crm_token_returns_401(): void
    {
        $this->postJson('/api/contact-supports/public', $this->payload())
            ->assertUnauthorized();

        $this->assertDatabaseCount('contact_supports', 0);
    }

    public function test_the_honeypot_descriptor_requires_crm_token(): void
    {
        $this->withHeaders($this->crmHeaders())
            ->getJson('/api/contact-supports/public/honeypot')
            ->assertOk()
            ->assertJsonStructure(['nameFieldName', 'validFromFieldName', 'encryptedValidFrom']);
    }

    public function test_a_landing_page_submission_is_stored_and_returns_201(): void
    {
        $this->withHeaders($this->crmHeaders())
            ->postJson('/api/contact-supports/public', $this->payload())
            ->assertCreated()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('contact_supports', [
            'email' => 'ada@example.com',
            'is_spam' => false,
        ]);
    }

    public function test_validation_errors_return_422(): void
    {
        $this->withHeaders($this->crmHeaders())
            ->postJson('/api/contact-supports/public', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'phone', 'subject', 'message']);
    }

    public function test_spammy_content_is_stored_but_flagged(): void
    {
        $this->withHeaders($this->crmHeaders())
            ->postJson('/api/contact-supports/public', $this->payload([
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
        $this->withHeaders($this->crmHeaders())
            ->postJson('/api/contact-supports/public', $this->payload([
                'my_name' => 'http://spam.example',
                'valid_from' => EncryptedTime::create(now()->subMinute()),
            ]))->assertCreated();

        // Bot learns nothing (identical response) but nothing is persisted.
        $this->assertDatabaseCount('contact_supports', 0);
    }
}

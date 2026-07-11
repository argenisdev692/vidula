<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Honeypot\EncryptedTime;
use Tests\TestCase;

/**
 * Public (guest) contact form + anti-spam pipeline: honeypot bot layer
 * (ProtectAgainstSpam) and SpamGuard content scoring. The form is unauthenticated
 * and stores flagged spam silently rather than rejecting it.
 */
final class PublicContactSubmissionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid submission payload including the two honeypot fields the middleware
     * expects: an EMPTY randomized name field (base name `my_name` matches the
     * randomized prefix) and a PAST encrypted timestamp so the time-trap passes.
     *
     * @param  array<string, string>  $overrides
     * @return array<string, string>
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
            'sms_consent' => '1',
            'my_name' => '',
            'valid_from' => EncryptedTime::create(now()->subMinute()),
            ...$overrides,
        ];
    }

    public function test_the_public_contact_page_is_reachable_by_guests(): void
    {
        $this->get('/contact')->assertOk();
    }

    public function test_a_legitimate_submission_is_stored_and_not_flagged(): void
    {
        $this->post('/contact', $this->payload())->assertRedirect();

        $this->assertDatabaseHas('contact_supports', [
            'first_name' => 'Ada',
            'email' => 'ada@example.com',
            'is_spam' => false,
            'spam_score' => 0,
            'readed' => false,
        ]);
    }

    public function test_a_bot_that_fills_the_honeypot_is_dropped(): void
    {
        $this->post('/contact', $this->payload(['my_name' => 'http://spam.example']));

        $this->assertDatabaseCount('contact_supports', 0);
    }

    public function test_a_submission_faster_than_the_time_trap_is_dropped(): void
    {
        // valid_from set in the FUTURE — mimics an instant (bot) submission.
        $this->post('/contact', $this->payload([
            'valid_from' => EncryptedTime::create(now()->addMinute()),
        ]));

        $this->assertDatabaseCount('contact_supports', 0);
    }

    public function test_spammy_content_is_stored_but_flagged_for_review(): void
    {
        $this->post('/contact', $this->payload([
            'subject' => 'Special offer for you',
            'message' => 'Cheap viagra and casino bonuses! Visit http://a.co and http://b.co now.',
        ]))->assertRedirect();

        $this->assertDatabaseHas('contact_supports', [
            'email' => 'ada@example.com',
            'is_spam' => true,
        ]);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->post('/contact', [
            'my_name' => '',
            'valid_from' => EncryptedTime::create(now()->subMinute()),
        ])->assertSessionHasErrors(['first_name', 'last_name', 'email', 'phone', 'subject', 'message']);
    }
}

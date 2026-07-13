<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\ContactSupport\Infrastructure\Broadcasting\ContactSupportSubmitted;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;
use Spatie\Honeypot\EncryptedTime;
use Tests\TestCase;

/**
 * Navbar notification-bell feed for the Contact Support inbox: unread count +
 * recent items, mark-all-read, and the real-time broadcast fired by a public
 * submission.
 */
final class ContactSupportNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        return $admin;
    }

    public function test_notifications_feed_returns_unread_count_and_recent_items(): void
    {
        ContactSupportEloquentModel::factory()->count(2)->create(['readed' => false]);
        ContactSupportEloquentModel::factory()->create(['readed' => true]);

        $response = $this->actingAs($this->superAdmin())
            ->getJson('/contact-supports/notifications')
            ->assertOk();

        $response->assertJson(['unread_count' => 2]);
        $this->assertCount(3, $response->json('items'));
    }

    public function test_notifications_feed_requires_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/contact-supports/notifications')
            ->assertForbidden();
    }

    public function test_mark_all_read_flips_every_unread_row(): void
    {
        ContactSupportEloquentModel::factory()->count(3)->create(['readed' => false]);

        $this->actingAs($this->superAdmin())
            ->postJson('/contact-supports/mark-all-read')
            ->assertOk()
            ->assertJson(['updated' => 3]);

        $this->assertDatabaseCount('contact_supports', 3);
        $this->assertSame(0, ContactSupportEloquentModel::query()->where('readed', false)->count());
    }

    public function test_mark_all_read_requires_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/contact-supports/mark-all-read')
            ->assertForbidden();
    }

    public function test_mark_read_still_redirects_for_non_json_requests(): void
    {
        $contact = ContactSupportEloquentModel::factory()->create(['readed' => false]);

        $this->actingAs($this->superAdmin())
            ->patch("/contact-supports/{$contact->uuid}/read")
            ->assertRedirect();

        $this->assertTrue($contact->refresh()->readed);
    }

    public function test_a_public_submission_broadcasts_a_notification(): void
    {
        Event::fake([ContactSupportSubmitted::class]);

        $this->post('/contact', [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+15551234567',
            'subject' => 'Billing question',
            'message' => 'I need help understanding my last invoice, please.',
            'sms_consent' => '1',
            'my_name' => '',
            'valid_from' => EncryptedTime::create(now()->subMinute()),
        ])->assertRedirect();

        Event::assertDispatched(ContactSupportSubmitted::class);
    }
}

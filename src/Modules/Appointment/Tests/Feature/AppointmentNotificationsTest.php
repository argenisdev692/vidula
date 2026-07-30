<?php

declare(strict_types=1);

namespace Modules\Appointment\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Appointment\Infrastructure\Broadcasting\AppointmentSubmitted;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Modules\Availability\Infrastructure\Persistence\Eloquent\Models\AvailabilityRuleEloquentModel;
use Tests\TestCase;

/**
 * Navbar notification-bell feed for the Appointment pipeline: unread count +
 * recent leads, mark-all-read, and the real-time broadcast fired by a public
 * booking.
 */
final class AppointmentNotificationsTest extends TestCase
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

    private function seedOpenFriday(): void
    {
        AvailabilityRuleEloquentModel::factory()->forDay(5)->slot('09:00', '13:00')->create();
    }

    public function test_notifications_feed_returns_unread_count_and_recent_items(): void
    {
        AppointmentEloquentModel::factory()->count(2)->create(['readed' => false]);
        AppointmentEloquentModel::factory()->create(['readed' => true]);

        $response = $this->actingAs($this->superAdmin())
            ->getJson('/appointments/notifications')
            ->assertOk();

        $response->assertJson(['unread_count' => 2]);
        $this->assertCount(3, $response->json('items'));
    }

    public function test_notifications_feed_requires_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/appointments/notifications')
            ->assertForbidden();
    }

    public function test_mark_all_read_flips_every_unread_row(): void
    {
        AppointmentEloquentModel::factory()->count(3)->create(['readed' => false]);

        $this->actingAs($this->superAdmin())
            ->postJson('/appointments/mark-all-read')
            ->assertOk()
            ->assertJson(['updated' => 3]);

        $this->assertSame(0, AppointmentEloquentModel::query()->where('readed', false)->count());
    }

    public function test_mark_all_read_requires_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/appointments/mark-all-read')
            ->assertForbidden();
    }

    public function test_mark_read_still_redirects_for_non_json_requests(): void
    {
        $appointment = AppointmentEloquentModel::factory()->create(['readed' => false]);

        $this->actingAs($this->superAdmin())
            ->patch("/appointments/{$appointment->uuid}/read")
            ->assertRedirect();

        $this->assertTrue($appointment->refresh()->readed);
    }

    public function test_a_public_booking_broadcasts_a_notification(): void
    {
        Event::fake([AppointmentSubmitted::class]);
        $this->seedOpenFriday();

        $this->withHeaders($this->crmHeaders())
            ->postJson('/api/appointments', [
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'client_type' => 'individual',
                'company_name' => null,
                'project_type' => 'new_website',
                'email' => 'ada@example.com',
                'phone' => '+15551234567',
                'address' => '123 Main St',
                'address_2' => null,
                'zip_code' => '10001',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'United States',
                'country_code' => 'US',
                'latitude' => null,
                'longitude' => null,
                'scheduled_at' => '2026-12-11 10:00:00',
                'sms_consent' => true,
                'notes' => 'Looking forward to it.',
            ])->assertCreated();

        Event::assertDispatched(AppointmentSubmitted::class);
    }
}

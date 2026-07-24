<?php

declare(strict_types=1);

namespace Modules\Meeting\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Tests\TestCase;

final class MeetingLeadPrefillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_create_page_prefills_attendee_from_lead_query_param(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        $lead = AppointmentEloquentModel::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Lead',
            'company_name' => 'Acme',
        ]);

        $this->actingAs($admin)
            ->get("/meetings/create?lead={$lead->uuid}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('meetings/Create')
                ->has('prefill.attendees', 1)
                ->where('prefill.attendees.0.type', 'lead')
                ->where('prefill.attendees.0.uuid', $lead->uuid)
                ->where('prefill.title', 'Meeting with Jane Lead'));
    }
}

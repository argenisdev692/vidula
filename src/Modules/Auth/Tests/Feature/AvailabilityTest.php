<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_check_email_availability_on_the_register_form(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->getJson('/register/availability?field=email&value=free@example.com')
            ->assertOk()
            ->assertExactJson(['available' => true]);

        $this->getJson('/register/availability?field=email&value=taken@example.com')
            ->assertOk()
            ->assertExactJson(['available' => false]);
    }

    public function test_a_malformed_email_reports_unavailable(): void
    {
        $this->getJson('/register/availability?field=email&value=not-an-email')
            ->assertOk()
            ->assertExactJson(['available' => false]);
    }

    public function test_the_profile_check_excludes_the_authenticated_user(): void
    {
        $user = User::factory()->create([
            'email' => 'me@example.com',
            'username' => 'currentuser',
        ]);

        // The caller's own current values read as available (unchanged).
        $this->actingAs($user)
            ->getJson('/profile/availability?field=email&value=me@example.com')
            ->assertOk()
            ->assertExactJson(['available' => true]);

        $this->actingAs($user)
            ->getJson('/profile/availability?field=username&value=currentuser')
            ->assertOk()
            ->assertExactJson(['available' => true]);
    }

    public function test_the_profile_check_flags_another_users_email(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);
        User::factory()->create(['email' => 'someone@example.com']);

        $this->actingAs($user)
            ->getJson('/profile/availability?field=email&value=someone@example.com')
            ->assertOk()
            ->assertExactJson(['available' => false]);
    }

    public function test_the_profile_check_requires_authentication(): void
    {
        $this->getJson('/profile/availability?field=email&value=free@example.com')
            ->assertUnauthorized();
    }
}

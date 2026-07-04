<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class AccountActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function pendingUser(): User
    {
        return User::factory()->unverified()->create(['password' => null]);
    }

    private function activationUrl(User $user): string
    {
        return URL::temporarySignedRoute('users.activation.show', now()->addHours(72), ['uuid' => $user->uuid]);
    }

    public function test_activation_sets_password_verifies_email_and_logs_in(): void
    {
        $user = $this->pendingUser();

        $this->post($this->activationUrl($user), [
            'password' => 'Sup3rS3cret!2026',
            'password_confirmation' => 'Sup3rS3cret!2026',
        ])->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertNotNull($user->password, 'Password must be set.');
        $this->assertNotNull($user->email_verified_at, 'Email must be verified in the same step.');
        $this->assertAuthenticatedAs($user);
    }

    public function test_activation_link_is_single_use(): void
    {
        $user = $this->pendingUser();
        $url = $this->activationUrl($user);

        $this->post($url, [
            'password' => 'Sup3rS3cret!2026',
            'password_confirmation' => 'Sup3rS3cret!2026',
        ])->assertRedirect();

        // Re-using the same link after activation is rejected (410 Gone).
        $this->post($url, [
            'password' => 'An0ther!Pass2026',
            'password_confirmation' => 'An0ther!Pass2026',
        ])->assertStatus(410);
    }

    public function test_activation_rejects_an_unsigned_link(): void
    {
        $user = $this->pendingUser();

        $this->get("/users/activate/{$user->uuid}")->assertStatus(403);
    }

    public function test_activation_enforces_the_password_policy(): void
    {
        $user = $this->pendingUser();

        $this->post($this->activationUrl($user), [
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertSessionHasErrors('password');

        $this->assertNull($user->fresh()->password);
    }
}

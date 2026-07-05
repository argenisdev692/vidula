<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Users\Infrastructure\Notifications\UserInvitationNotification;
use Tests\TestCase;

final class UserManagementTest extends TestCase
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

    public function test_super_admin_invites_a_pending_user_and_an_email_is_sent(): void
    {
        Notification::fake();

        $this->actingAs($this->superAdmin())
            ->post('/users', [
                'first_name' => 'New',
                'last_name' => 'Hire',
                'email' => 'new.hire@example.com',
            ])
            ->assertRedirect();

        $user = User::query()->where('email', 'new.hire@example.com')->firstOrFail();

        $this->assertNull($user->password, 'Invited user must be Pending (no password).');
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->invited_at);
        Notification::assertSentTo($user, UserInvitationNotification::class);
    }

    public function test_invite_and_update_persist_the_secondary_address(): void
    {
        Notification::fake();
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/users', [
                'first_name' => 'Apt',
                'email' => 'apt.dweller@example.com',
                'address_2' => 'Suite 4B',
            ])
            ->assertRedirect();

        $user = User::query()->where('email', 'apt.dweller@example.com')->firstOrFail();
        $this->assertSame('Suite 4B', $user->address_2);

        $this->actingAs($admin)
            ->put("/users/{$user->uuid}", [
                'first_name' => 'Apt',
                'email' => 'apt.dweller@example.com',
                'address_2' => 'Suite 9C',
            ])
            ->assertRedirect();

        $this->assertSame('Suite 9C', $user->refresh()->address_2);
    }

    public function test_invitation_email_uses_the_branded_template(): void
    {
        $html = view('emails.invitation', [
            'activationUrl' => 'https://vidula.test/users/activate/abc',
            'expiresInHours' => 72,
        ])->render();

        $this->assertStringContainsString('https://vidula.test/users/activate/abc', $html);
        $this->assertStringContainsString(__('Activate account'), $html);
        // Proves it extends emails.layout (brand gradient header), not the stock Laravel theme.
        $this->assertStringContainsString('linear-gradient', $html);
    }

    public function test_a_user_without_permission_cannot_manage_users(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');

        $this->actingAs($plain)->get('/users')->assertForbidden();
        $this->actingAs($plain)->post('/users', [
            'first_name' => 'X',
            'email' => 'x@example.com',
        ])->assertForbidden();
    }

    public function test_forced_password_change_redirects_to_the_update_screen(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->get('/')->assertRedirect(route('password.expired'));
    }

    public function test_bulk_delete_then_restore_users(): void
    {
        $admin = $this->superAdmin();
        $targets = User::factory()->count(3)->create();
        $uuids = $targets->pluck('uuid')->all();

        $this->actingAs($admin)->post('/users/bulk-delete', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertSoftDeleted('users', ['uuid' => $uuid]);
        }

        $this->actingAs($admin)->post('/users/bulk-restore', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertDatabaseHas('users', ['uuid' => $uuid, 'deleted_at' => null]);
        }
    }

    public function test_bulk_delete_rejects_more_than_500_uuids(): void
    {
        $admin = $this->superAdmin();
        $uuids = array_map(static fn (): string => (string) Str::uuid(), range(1, 501));

        $this->actingAs($admin)
            ->postJson('/users/bulk-delete', ['uuids' => $uuids])
            ->assertStatus(422)
            ->assertJsonValidationErrors('uuids');
    }

    public function test_csv_export_streams_with_permission(): void
    {
        $admin = $this->superAdmin();
        User::factory()->count(2)->create();

        $this->actingAs($admin)
            ->get('/users/export?format=csv')
            ->assertOk();
    }
}

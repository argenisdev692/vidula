<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;
use Tests\TestCase;

final class ContactSupportManagementTest extends TestCase
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

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ADA@Example.com',
            'phone' => '+15551234567',
            'subject' => 'Billing question',
            'message' => 'I need help understanding my last invoice.',
            'sms_consent' => '1',
        ];
    }

    public function test_super_admin_creates_a_contact_request_normalizing_the_email(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/contact-supports', $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('contact_supports', [
            'first_name' => 'Ada',
            'email' => 'ada@example.com',
            'readed' => false,
        ]);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/contact-supports', [])
            ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'phone', 'subject', 'message']);
    }

    public function test_mark_as_read_flips_the_readed_flag(): void
    {
        $contact = ContactSupportEloquentModel::factory()->create(['readed' => false]);

        $this->actingAs($this->superAdmin())
            ->patch("/contact-supports/{$contact->uuid}/read")
            ->assertRedirect();

        $this->assertDatabaseHas('contact_supports', [
            'uuid' => $contact->uuid,
            'readed' => true,
        ]);
    }

    public function test_update_edits_the_captured_data(): void
    {
        $contact = ContactSupportEloquentModel::factory()->create();

        $this->actingAs($this->superAdmin())
            ->put("/contact-supports/{$contact->uuid}", [...$this->validPayload(), 'subject' => 'Updated subject'])
            ->assertRedirect();

        $this->assertDatabaseHas('contact_supports', [
            'uuid' => $contact->uuid,
            'subject' => 'Updated subject',
        ]);
    }

    public function test_delete_then_restore_a_contact_request(): void
    {
        $admin = $this->superAdmin();
        $contact = ContactSupportEloquentModel::factory()->create();

        $this->actingAs($admin)->delete("/contact-supports/{$contact->uuid}")->assertRedirect();
        $this->assertSoftDeleted('contact_supports', ['uuid' => $contact->uuid]);

        $this->actingAs($admin)->post("/contact-supports/{$contact->uuid}/restore")->assertRedirect();
        $this->assertDatabaseHas('contact_supports', ['uuid' => $contact->uuid, 'deleted_at' => null]);
    }

    public function test_bulk_delete_then_restore(): void
    {
        $admin = $this->superAdmin();
        $uuids = ContactSupportEloquentModel::factory()->count(3)->create()->pluck('uuid')->all();

        $this->actingAs($admin)->post('/contact-supports/bulk-delete', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertSoftDeleted('contact_supports', ['uuid' => $uuid]);
        }

        $this->actingAs($admin)->post('/contact-supports/bulk-restore', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertDatabaseHas('contact_supports', ['uuid' => $uuid, 'deleted_at' => null]);
        }
    }

    public function test_bulk_delete_rejects_more_than_500_uuids(): void
    {
        $uuids = array_map(static fn (): string => (string) Str::uuid7(), range(1, 501));

        $this->actingAs($this->superAdmin())
            ->postJson('/contact-supports/bulk-delete', ['uuids' => $uuids])
            ->assertStatus(422)
            ->assertJsonValidationErrors('uuids');
    }

    public function test_read_filter_narrows_the_list(): void
    {
        ContactSupportEloquentModel::factory()->read()->create(['subject' => 'Already handled']);
        ContactSupportEloquentModel::factory()->create(['subject' => 'Still pending']);

        $this->actingAs($this->superAdmin())
            ->getJson('/contact-supports?read=unread')
            ->assertOk()
            ->assertJsonFragment(['subject' => 'Still pending'])
            ->assertJsonMissing(['subject' => 'Already handled']);
    }

    public function test_search_filter_narrows_the_list(): void
    {
        ContactSupportEloquentModel::factory()->create(['email' => 'grace@navy.mil']);
        ContactSupportEloquentModel::factory()->create(['email' => 'someone@else.com']);

        $this->actingAs($this->superAdmin())
            ->getJson('/contact-supports?search=grace@navy.mil')
            ->assertOk()
            ->assertJsonFragment(['email' => 'grace@navy.mil'])
            ->assertJsonMissing(['email' => 'someone@else.com']);
    }

    public function test_exports_the_inbox_as_excel(): void
    {
        ContactSupportEloquentModel::factory()->count(2)->create();

        $this->actingAs($this->superAdmin())
            ->get('/contact-supports/export?format=xlsx')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_exports_the_inbox_as_pdf(): void
    {
        ContactSupportEloquentModel::factory()->count(2)->create();

        $this->actingAs($this->superAdmin())
            ->get('/contact-supports/export?format=pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_export_rejects_an_unknown_format(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/contact-supports/export?format=exe')
            ->assertStatus(422);
    }

    public function test_a_user_without_permission_cannot_manage_contact_requests(): void
    {
        $plain = User::factory()->create();
        $plain->assignRole('USER');

        $this->actingAs($plain)->get('/contact-supports')->assertForbidden();
        $this->actingAs($plain)->post('/contact-supports', $this->validPayload())->assertForbidden();
    }
}

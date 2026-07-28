<?php

declare(strict_types=1);

namespace Modules\Clients\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;
use Tests\TestCase;

final class ClientManagementTest extends TestCase
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
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'client_name' => 'Acme Corp',
            'email' => 'ops@acme.test',
            'status' => 'DRAFT',
            'phone' => '+15551234567',
            'address' => '1 Main St',
            'tax_id' => null,
            'nif' => null,
            'website' => null,
            'facebook_link' => null,
            'instagram_link' => null,
            'linkedin_link' => null,
            'twitter_link' => null,
            'notes' => null,
            ...$overrides,
        ];
    }

    public function test_super_admin_creates_a_client_owned_by_themselves(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/clients', $this->validPayload())
            ->assertRedirect();

        $client = ClientEloquentModel::query()->where('client_name', 'Acme Corp')->firstOrFail();

        $this->assertSame($admin->id, $client->user_id);
        $this->assertSame('DRAFT', $client->status);
        $this->assertSame('+15551234567', $client->phone);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/clients', $this->validPayload(['phone' => '555 000 0000']))
            ->assertSessionHasErrors('phone');
    }

    public function test_update_changes_name_and_lifecycle_status(): void
    {
        $admin = $this->superAdmin();
        $client = ClientEloquentModel::factory()->create(['client_name' => 'Old Name', 'status' => 'DRAFT']);

        $this->actingAs($admin)->put("/clients/{$client->uuid}", $this->validPayload([
            'client_name' => 'New Name',
            'status' => 'ACTIVE',
            'email' => $client->email,
        ]))->assertRedirect();

        $client->refresh();
        $this->assertSame('New Name', $client->client_name);
        $this->assertSame('ACTIVE', $client->status);
    }

    public function test_delete_then_restore_a_client(): void
    {
        $admin = $this->superAdmin();
        $client = ClientEloquentModel::factory()->create();

        $this->actingAs($admin)->delete("/clients/{$client->uuid}")->assertRedirect();
        $this->assertSoftDeleted('clients', ['uuid' => $client->uuid]);

        $this->actingAs($admin)->post("/clients/{$client->uuid}/restore")->assertRedirect();
        $this->assertDatabaseHas('clients', ['uuid' => $client->uuid, 'deleted_at' => null]);
    }

    public function test_bulk_delete_then_restore(): void
    {
        $admin = $this->superAdmin();
        $uuids = ClientEloquentModel::factory()->count(3)->create()->pluck('uuid')->all();

        $this->actingAs($admin)->post('/clients/bulk-delete', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertSoftDeleted('clients', ['uuid' => $uuid]);
        }

        $this->actingAs($admin)->post('/clients/bulk-restore', ['uuids' => $uuids])->assertRedirect();
        foreach ($uuids as $uuid) {
            $this->assertDatabaseHas('clients', ['uuid' => $uuid, 'deleted_at' => null]);
        }
    }

    public function test_bulk_delete_rejects_more_than_500_uuids(): void
    {
        $uuids = array_map(static fn (): string => (string) Str::uuid(), range(1, 501));

        $this->actingAs($this->superAdmin())
            ->postJson('/clients/bulk-delete', ['uuids' => $uuids])
            ->assertStatus(422)
            ->assertJsonValidationErrors('uuids');
    }

    public function test_search_filter_narrows_the_list(): void
    {
        ClientEloquentModel::factory()->create(['client_name' => 'Northern Lights Agency']);
        ClientEloquentModel::factory()->create(['client_name' => 'Southern Gardens']);

        $this->actingAs($this->superAdmin())
            ->getJson('/clients?search=Northern')
            ->assertOk()
            ->assertJsonFragment(['client_name' => 'Northern Lights Agency'])
            ->assertJsonMissing(['client_name' => 'Southern Gardens']);
    }

    public function test_user_role_cannot_create_clients(): void
    {
        $user = User::factory()->create();
        $user->assignRole('USER');

        $this->actingAs($user)
            ->post('/clients', $this->validPayload())
            ->assertForbidden();
    }

    public function test_export_csv_streams_successfully(): void
    {
        ClientEloquentModel::factory()->create(['client_name' => 'Exportable Client']);

        $this->actingAs($this->superAdmin())
            ->get('/clients/export?format=csv')
            ->assertOk();
    }

    public function test_export_xlsx_streams_successfully(): void
    {
        ClientEloquentModel::factory()->create(['client_name' => 'Xlsx Client']);

        $this->actingAs($this->superAdmin())
            ->get('/clients/export?format=xlsx')
            ->assertOk();
    }

    public function test_export_pdf_renders_successfully(): void
    {
        ClientEloquentModel::factory()->create(['client_name' => 'Pdf Client']);

        $this->actingAs($this->superAdmin())
            ->get('/clients/export?format=pdf')
            ->assertOk();
    }

    public function test_show_includes_relation_counts(): void
    {
        $client = ClientEloquentModel::factory()->create(['client_name' => 'Counted Client']);

        $this->actingAs($this->superAdmin())
            ->getJson("/clients/{$client->uuid}")
            ->assertOk()
            ->assertJsonPath('data.client_name', 'Counted Client')
            ->assertJsonPath('data.invoices_count', 0)
            ->assertJsonPath('data.products_count', 0);
    }
}

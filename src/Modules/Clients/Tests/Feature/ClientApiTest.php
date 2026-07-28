<?php

declare(strict_types=1);

namespace Modules\Clients\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;
use Tests\TestCase;

final class ClientApiTest extends TestCase
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

    public function test_sanctum_lists_clients(): void
    {
        Sanctum::actingAs($this->superAdmin());
        ClientEloquentModel::factory()->create(['client_name' => 'API Client']);

        $this->getJson('/api/clients')
            ->assertOk()
            ->assertJsonFragment(['client_name' => 'API Client']);
    }

    public function test_sanctum_shows_a_client_by_uuid(): void
    {
        Sanctum::actingAs($this->superAdmin());
        $client = ClientEloquentModel::factory()->create(['client_name' => 'Shown Client']);

        $this->getJson("/api/clients/{$client->uuid}")
            ->assertOk()
            ->assertJsonPath('data.client_name', 'Shown Client');
    }

    public function test_unauthenticated_api_is_rejected(): void
    {
        $this->getJson('/api/clients')->assertUnauthorized();
    }

    public function test_user_role_cannot_list_clients_via_api(): void
    {
        $user = User::factory()->create();
        $user->assignRole('USER');
        Sanctum::actingAs($user);

        $this->getJson('/api/clients')->assertForbidden();
    }
}

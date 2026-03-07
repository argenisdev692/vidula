<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel as User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createSuperAdminUser(): User
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    PermissionEloquentModel::firstOrCreate(['name' => 'VIEW_COMPANY_DATA', 'guard_name' => 'sanctum']);
    $role = Role::firstOrCreate(['name' => 'SUPER_ADMIN', 'guard_name' => 'sanctum']);
    $role->givePermissionTo('VIEW_COMPANY_DATA');

    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('SUPER_ADMIN');

    return $user;
}

it('exports company data to excel', function (): void {
    $response = $this->actingAs(createSuperAdminUser())
        ->get(route('api.admin.company_data.export', ['format' => 'excel']));

    $response->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('exports company data to pdf', function (): void {
    $response = $this->actingAs(createSuperAdminUser())
        ->get(route('api.admin.company_data.export', ['format' => 'pdf']));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

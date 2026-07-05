<?php

declare(strict_types=1);

namespace Modules\Company\Tests\Feature;

use App\Models\CompanyData;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CompanyBrandingTest extends TestCase
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

    private function seedCompany(): void
    {
        $owner = User::factory()->create();
        CompanyData::create([
            'company_name' => 'Vidula',
            'email' => 'brand@example.com',
            'user_id' => $owner->id,
        ]);
    }

    public function test_super_admin_uploads_a_png_logo_to_r2(): void
    {
        Storage::fake('r2');
        $this->seedCompany();

        $this->actingAs($this->superAdmin())
            ->post('/settings/company/logo', [
                'type' => 'logo_white',
                'logo' => UploadedFile::fake()->image('logo.png', 240, 80),
            ])
            ->assertRedirect();

        $company = CompanyData::query()->firstOrFail();
        $this->assertNotNull($company->logo_white_path);
        Storage::disk('r2')->assertExists($company->logo_white_path);
    }

    public function test_a_non_png_file_is_rejected(): void
    {
        Storage::fake('r2');
        $this->seedCompany();

        $this->actingAs($this->superAdmin())
            ->post('/settings/company/logo', [
                'type' => 'logo',
                'logo' => UploadedFile::fake()->image('logo.jpg', 240, 80),
            ])
            ->assertSessionHasErrors('logo');
    }

    public function test_an_unknown_logo_type_is_rejected(): void
    {
        Storage::fake('r2');
        $this->seedCompany();

        $this->actingAs($this->superAdmin())
            ->post('/settings/company/logo', [
                'type' => 'banner',
                'logo' => UploadedFile::fake()->image('logo.png', 240, 80),
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_a_user_without_permission_cannot_upload_a_logo(): void
    {
        Storage::fake('r2');
        $this->seedCompany();

        $plain = User::factory()->create();
        $plain->assignRole('USER');

        $this->actingAs($plain)
            ->post('/settings/company/logo', [
                'type' => 'logo',
                'logo' => UploadedFile::fake()->image('logo.png', 240, 80),
            ])
            ->assertForbidden();
    }
}

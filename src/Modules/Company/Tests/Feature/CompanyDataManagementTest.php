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

final class CompanyDataManagementTest extends TestCase
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

    private function seedCompany(): CompanyData
    {
        $owner = User::factory()->create();

        return CompanyData::create([
            'company_name' => 'Vidula',
            'email' => 'brand@example.com',
            'user_id' => $owner->id,
        ]);
    }

    public function test_super_admin_lists_the_company_singleton(): void
    {
        $company = $this->seedCompany();

        $this->actingAs($this->superAdmin())
            ->getJson('/company-data')
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $company->uuid);
    }

    public function test_super_admin_shows_the_company_record(): void
    {
        $company = $this->seedCompany();

        $this->actingAs($this->superAdmin())
            ->getJson("/company-data/{$company->uuid}")
            ->assertOk()
            ->assertJsonPath('data.uuid', $company->uuid);
    }

    public function test_super_admin_updates_company_text_fields(): void
    {
        $company = $this->seedCompany();

        $this->actingAs($this->superAdmin())
            ->from("/company-data/{$company->uuid}")
            ->put("/company-data/{$company->uuid}", [
                'company_name' => 'Vidula Lda',
                'email' => 'hello@vidula.pt',
                'website' => 'https://vidula.pt',
                'tiktok_link' => 'https://www.tiktok.com/@vidula',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $company->refresh();
        $this->assertSame('Vidula Lda', $company->company_name);
        $this->assertSame('hello@vidula.pt', $company->email);
        $this->assertSame('https://vidula.pt', $company->website);
    }

    public function test_update_requires_a_company_name(): void
    {
        $company = $this->seedCompany();

        $this->actingAs($this->superAdmin())
            ->from("/company-data/{$company->uuid}")
            ->put("/company-data/{$company->uuid}", [
                'company_name' => '',
            ])
            ->assertSessionHasErrors('company_name');
    }

    public function test_update_rejects_a_malformed_website(): void
    {
        $company = $this->seedCompany();

        $this->actingAs($this->superAdmin())
            ->from("/company-data/{$company->uuid}")
            ->put("/company-data/{$company->uuid}", [
                'company_name' => 'Vidula',
                'website' => 'not-a-url',
            ])
            ->assertSessionHasErrors('website');
    }

    public function test_super_admin_uploads_a_signature(): void
    {
        Storage::fake('r2');
        $this->seedCompany();

        $this->actingAs($this->superAdmin())
            ->post('/settings/company/signature', [
                'signature' => UploadedFile::fake()->image('signature.png', 300, 120),
            ])
            ->assertRedirect();

        $company = CompanyData::query()->firstOrFail();
        $this->assertNotNull($company->signature_path);
        Storage::disk('r2')->assertExists($company->signature_path);
    }

    public function test_super_admin_deletes_a_logo_asset(): void
    {
        Storage::fake('r2');
        $company = $this->seedCompany();
        Storage::disk('r2')->put('branding/logo.png', 'binary');
        $company->forceFill(['logo_path' => 'branding/logo.png'])->save();

        $this->actingAs($this->superAdmin())
            ->delete('/settings/company/logo/logo')
            ->assertRedirect();

        $company->refresh();
        $this->assertNull($company->logo_path);
        Storage::disk('r2')->assertMissing('branding/logo.png');
    }

    public function test_a_user_without_permission_cannot_update_company_data(): void
    {
        $company = $this->seedCompany();

        $plain = User::factory()->create();
        $plain->assignRole('USER');

        $this->actingAs($plain)
            ->from("/company-data/{$company->uuid}")
            ->put("/company-data/{$company->uuid}", [
                'company_name' => 'Hacked Inc',
            ])
            ->assertForbidden();
    }
}

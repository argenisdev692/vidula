<?php

declare(strict_types=1);

namespace Modules\Company\Tests\Feature;

use App\Models\CompanyData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRM-token-gated landing payload: GET /api/company-data/public.
 */
final class PublicCompanyDataApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedCompany(): CompanyData
    {
        $owner = User::factory()->create();

        return CompanyData::create([
            'company_name' => 'Vidula Studio',
            'description' => 'Software studio',
            'email' => 'hello@vidula.test',
            'phone' => '+15550001111',
            'website' => 'https://vidula.test',
            'city' => 'Lisbon',
            'country' => 'Portugal',
            'country_code' => 'PT',
            'latitude' => 38.7223,
            'longitude' => -9.1393,
            'linkedin_link' => 'https://linkedin.com/company/vidula',
            'user_id' => $owner->id,
        ]);
    }

    public function test_without_crm_token_returns_401(): void
    {
        $this->seedCompany();

        $this->getJson('/api/company-data/public')
            ->assertUnauthorized();
    }

    public function test_with_invalid_crm_token_returns_401(): void
    {
        $this->seedCompany();

        $this->withHeaders(['Authorization' => 'Bearer wrong-token'])
            ->getJson('/api/company-data/public')
            ->assertUnauthorized();
    }

    public function test_public_company_payload_is_allowlisted(): void
    {
        $this->seedCompany();

        $this->withHeaders($this->crmHeaders())
            ->getJson('/api/company-data/public')
            ->assertOk()
            ->assertJsonPath('name', 'Vidula Studio')
            ->assertJsonPath('email', 'hello@vidula.test')
            ->assertJsonPath('phone', '+15550001111')
            ->assertJsonPath('city', 'Lisbon')
            ->assertJsonPath('country_code', 'PT')
            ->assertJsonPath('socials.linkedin', 'https://linkedin.com/company/vidula')
            ->assertJsonMissingPath('bank_iban')
            ->assertJsonMissingPath('nif_nipc')
            ->assertJsonMissingPath('user_id')
            ->assertJsonStructure([
                'name',
                'description',
                'website',
                'logo_url',
                'logo_white_url',
                'mark_url',
                'email',
                'phone',
                'address',
                'address_2',
                'zip_code',
                'city',
                'state',
                'country',
                'country_code',
                'latitude',
                'longitude',
                'socials',
            ]);
    }

    public function test_x_crm_api_token_header_is_accepted(): void
    {
        $this->seedCompany();

        $this->withHeaders([
            'X-CRM-Api-Token' => (string) config('services.crm.api_token'),
        ])
            ->getJson('/api/company-data/public')
            ->assertOk()
            ->assertJsonPath('name', 'Vidula Studio');
    }
}

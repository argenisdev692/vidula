<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CompanyData;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the main company record including fiscal identity and bank details
 * used on invoice PDFs.
 */
class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::query()
            ->where('email', 'argenis692@gmail.com')
            ->value('id');

        if (! is_int($userId)) {
            throw new \RuntimeException('UserSeeder must create argenis692@gmail.com before CompanySeeder runs.');
        }

        $company = CompanyData::query()->firstOrNew(['email' => 'argenis692@gmail.com']);
        $company->fill([
            'uuid' => (string) Str::uuid7(), // Se añade el UUID aquí
            'name' => 'Argenis Carrillo Gonzalez',
            'company_name' => 'Vidula',
            'description' => 'Vidula is an AI-powered workspace for creators and educators: classroom '
                .'management, AI-assisted content generation, social media management and social media '
                .'campaign scheduling, all in one platform.',
            'signature_path' => null,
            'email' => 'argenis692@gmail.com',
            'phone' => '+351 963 490 414',
            'nif_nipc' => '316416584',
            'nie' => '2175V64V7',
            'bank_beneficiary' => 'Argenis Jose Carrillo Gonzalez',
            'bank_iban' => 'PT50 0036 0011 9910 0063 053 49',
            'bank_bic' => 'MPIOPTPL',
            'bank_name' => 'Montepio',
            'invoice_notes' => "VAT - Reverse Charge: International transaction exempt from VAT. Cross-border service provision between Portugal and United States (B2B).\nWeb development services provided remotely.",
            'address' => 'Rua da Saudade, Nº 1, R/C Esq.',
            'address_2' => null,
            'zip_code' => '6200-386',
            'city' => 'Covilhã',
            'state' => 'Castelo Branco',
            'country' => 'Portugal',
            'country_code' => 'PT',
            'website' => null,
            'user_id' => $userId,
            'facebook_link' => null,
            'instagram_link' => null,
            'linkedin_link' => null,
            'twitter_link' => null,
            'tiktok_link' => 'https://www.tiktok.com/@vidula',
            'latitude' => 40.2806,
            'longitude' => -7.5049,
        ]);
        $company->save();
    }
}

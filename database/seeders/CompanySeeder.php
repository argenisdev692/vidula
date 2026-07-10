<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CompanyData;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the main company record. Only columns that exist in the
 * `company_data` migration are populated.
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
            'signature_path' => null,
            'email' => 'argenis692@gmail.com',
            'phone' => '+351 963 490 414',
            'address' => 'Rua da Saudade, Nº 1, R/C Esq., 6200-386 Covilhã, Portugal',
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

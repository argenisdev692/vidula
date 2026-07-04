<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Company;

use App\Models\CompanyData;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Single source of company branding for emails (and anywhere else).
 *
 * DB-first: reads the main `company_data` row so no env vars are required.
 * Falls back to config/company.php when the table is empty or unavailable
 * (e.g. before the first migrate). Cached briefly to avoid a query per email.
 * `logo_url` has no DB column, so it always comes from config.
 *
 * @phpstan-type CompanyArray array{name: string, url: ?string, logo_url: ?string, address: ?string, support_email: ?string, socials: array<string, string>}
 */
final class CompanyProfile
{
    private const string CACHE_KEY = 'company.profile';

    /**
     * @return CompanyArray
     */
    public static function data(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(30), static function (): array {
            try {
                $company = CompanyData::query()->orderBy('id')->first();
            } catch (Throwable) {
                $company = null;
            }

            if ($company === null) {
                return self::fromConfig();
            }

            return [
                'name' => (string) ($company->company_name ?: config('company.name')),
                'url' => $company->website ?: config('company.url'),
                'logo_url' => config('company.logo_url'),
                'address' => $company->address ?: config('company.address'),
                'support_email' => $company->email ?: config('company.support_email'),
                'socials' => array_filter([
                    'linkedin' => $company->linkedin_link,
                    'twitter' => $company->twitter_link,
                    'instagram' => $company->instagram_link,
                    'facebook' => $company->facebook_link,
                ]),
            ];
        });
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return CompanyArray
     */
    private static function fromConfig(): array
    {
        return [
            'name' => (string) config('company.name'),
            'url' => config('company.url'),
            'logo_url' => config('company.logo_url'),
            'address' => config('company.address'),
            'support_email' => config('company.support_email'),
            'socials' => config('company.socials', []),
        ];
    }
}

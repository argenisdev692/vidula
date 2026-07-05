<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Company;

use App\Models\CompanyData;
use Illuminate\Support\Facades\Cache;
use Shared\Domain\Ports\StoragePort;
use Throwable;

/**
 * Single source of company branding for emails, the hero and the navbar.
 *
 * DB-first: reads the main `company_data` row so NO company-specific env vars
 * are required. When the table is empty or unavailable (e.g. before the first
 * migrate) it falls back to the app's own APP_NAME / APP_URL / MAIL_FROM_ADDRESS
 * and the bundled logos shipped in `public/img`. Cached briefly to avoid a query
 * per email/render.
 *
 * Uploaded logos live on Cloudflare R2 (public visibility); the stored column
 * holds the R2 object key, resolved to a permanent public URL via StoragePort.
 * A null column falls back to the bundled asset served from APP_URL. R2 failures
 * degrade to the bundled asset rather than breaking every render.
 *
 * @phpstan-type CompanyArray array{name: string, url: ?string, logo_url: string, logo_white_url: string, mark_url: string, address: ?string, support_email: ?string, socials: array<string, string>}
 */
final class CompanyProfile
{
    private const string CACHE_KEY = 'company.profile';

    private const string FALLBACK_LOGO = 'img/Logo.png';

    private const string FALLBACK_LOGO_WHITE = 'img/Logo-white.png';

    private const string FALLBACK_MARK = 'img/Mark.png';

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

            return [
                'name' => (string) ($company?->company_name ?: config('app.name')),
                'url' => $company?->website ?: config('app.url'),
                'logo_url' => self::logoUrl($company?->logo_path, self::FALLBACK_LOGO),
                'logo_white_url' => self::logoUrl($company?->logo_white_path, self::FALLBACK_LOGO_WHITE),
                'mark_url' => self::logoUrl($company?->mark_path, self::FALLBACK_MARK),
                'address' => $company?->address,
                'support_email' => $company?->email ?: config('mail.from.address'),
                'socials' => array_filter([
                    'linkedin' => $company?->linkedin_link,
                    'twitter' => $company?->twitter_link,
                    'instagram' => $company?->instagram_link,
                    'facebook' => $company?->facebook_link,
                    'tiktok' => $company?->tiktok_link,
                ]),
            ];
        });
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Resolve a stored logo reference to an absolute URL: an R2 object key →
     * permanent public URL; otherwise the bundled asset served from APP_URL.
     */
    private static function logoUrl(?string $key, string $fallback): string
    {
        if ($key === null || $key === '') {
            return self::bundledUrl($fallback);
        }

        try {
            return app(StoragePort::class)->publicUrl($key);
        } catch (Throwable) {
            return self::bundledUrl($fallback);
        }
    }

    private static function bundledUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }
}

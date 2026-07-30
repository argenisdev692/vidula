<?php

declare(strict_types=1);

namespace Modules\Company\Infrastructure\Http\Controllers\Api;

use Modules\Company\Application\Queries\GetPublicCompanyHandler;
use Modules\Company\Application\ReadModels\CompanyPublicReadModel;

/**
 * CRM-token-gated company payload for the Astro marketing site.
 * Shaped by {@see CompanyPublicReadModel} (OWASP §12 allowlist). Route uses
 * `crm.token` + throttle — no Sanctum session.
 */
final readonly class PublicCompanyDataController
{
    /**
     * Show public company data.
     *
     * Returns the seeded company singleton for the landing page (branding,
     * contact, socials, map coordinates). Bank / fiscal fields are never
     * included.
     */
    public function show(GetPublicCompanyHandler $get): CompanyPublicReadModel
    {
        return $get->handle();
    }
}

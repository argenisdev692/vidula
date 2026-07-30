<?php

declare(strict_types=1);

namespace Modules\Company\Application\Queries;

use Modules\Company\Application\ReadModels\CompanyPublicReadModel;

/**
 * Landing-page company singleton for Astro. Trust boundary differs from the
 * admin {@see GetCompanyHandler}: anonymous CRM-token clients only, allowlisted
 * fields via {@see CompanyPublicReadModel} (no bank / fiscal secrets).
 */
final readonly class GetPublicCompanyHandler
{
    public function handle(): CompanyPublicReadModel
    {
        return CompanyPublicReadModel::fromProfile();
    }
}

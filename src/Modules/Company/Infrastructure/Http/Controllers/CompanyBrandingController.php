<?php

declare(strict_types=1);

namespace Modules\Company\Infrastructure\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Company\Application\Commands\UpdateCompanyLogoHandler;
use Modules\Company\Application\DTOs\UpdateCompanyLogoData;

/**
 * Company branding management. Uploads the light logo, white logo and mark
 * (PNG) to R2; the DB-driven CompanyProfile then serves them to emails, the
 * login hero and the navbar. Authorized via `permission:UPDATE_COMPANY_DATA`.
 */
final readonly class CompanyBrandingController
{
    public function __construct(private UpdateCompanyLogoHandler $updateLogo) {}

    public function update(UpdateCompanyLogoData $data): RedirectResponse
    {
        $this->updateLogo->handle($data);

        return back()->with('success', __('Logo updated.'));
    }
}

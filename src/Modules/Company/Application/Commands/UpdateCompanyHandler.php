<?php

declare(strict_types=1);

namespace Modules\Company\Application\Commands;

use App\Models\CompanyData;
use Modules\Company\Application\DTOs\UpdateCompanyData;
use Modules\Company\Domain\Ports\CompanyRepositoryPort;

/**
 * Updates the company record's text fields. Persisting triggers the CompanyData
 * `saved` hook, which busts the CompanyProfile branding/contact cache.
 */
final readonly class UpdateCompanyHandler
{
    public function __construct(private CompanyRepositoryPort $companies) {}

    public function handle(CompanyData $company, UpdateCompanyData $data): CompanyData
    {
        return $this->companies->update($company, [
            'name' => $data->name,
            'company_name' => $data->companyName,
            'email' => $data->email,
            'phone' => $data->phone,
            'address' => $data->address,
            'address_2' => $data->address2,
            'website' => $data->website,
            'facebook_link' => $data->facebookLink,
            'instagram_link' => $data->instagramLink,
            'linkedin_link' => $data->linkedinLink,
            'twitter_link' => $data->twitterLink,
            'tiktok_link' => $data->tiktokLink,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
        ]);
    }
}

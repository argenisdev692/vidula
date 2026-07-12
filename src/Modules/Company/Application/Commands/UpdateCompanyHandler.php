<?php

declare(strict_types=1);

namespace Modules\Company\Application\Commands;

use App\Models\CompanyData;
use Illuminate\Support\Facades\DB;
use Modules\Company\Application\DTOs\UpdateCompanyData;
use Modules\Company\Domain\Events\CompanyCountryChanged;
use Modules\Company\Domain\Ports\CompanyRepositoryPort;

/**
 * Updates the company record's text fields. Persisting triggers the CompanyData
 * `saved` hook, which busts the CompanyProfile branding/contact cache. A change
 * to `country_code` raises {@see CompanyCountryChanged} so the Availability
 * module can rebuild national holidays for the new country.
 */
final readonly class UpdateCompanyHandler
{
    public function __construct(private CompanyRepositoryPort $companies) {}

    public function handle(CompanyData $company, UpdateCompanyData $data): CompanyData
    {
        $previousCountryCode = $company->country_code;

        $updated = DB::transaction(fn () => $this->companies->update($company, [
            'name' => $data->name,
            'company_name' => $data->companyName,
            'description' => $data->description,
            'email' => $data->email,
            'phone' => $data->phone,
            'address' => $data->address,
            'address_2' => $data->address2,
            'zip_code' => $data->zipCode,
            'city' => $data->city,
            'state' => $data->state,
            'country' => $data->country,
            'country_code' => $data->countryCode,
            'website' => $data->website,
            'facebook_link' => $data->facebookLink,
            'instagram_link' => $data->instagramLink,
            'linkedin_link' => $data->linkedinLink,
            'twitter_link' => $data->twitterLink,
            'tiktok_link' => $data->tiktokLink,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
        ]));

        if ($updated->country_code !== $previousCountryCode) {
            event(new CompanyCountryChanged($previousCountryCode, $updated->country_code));
        }

        return $updated;
    }
}

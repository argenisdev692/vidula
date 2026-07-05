<?php

declare(strict_types=1);

namespace Modules\Company\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Admin payload to update the single company record's text fields (contact,
 * fiscal name, address, social links, geolocation). Brand assets (logos,
 * signature) are handled by their own upload/delete endpoints — never here.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class UpdateCompanyData extends Data
{
    public function __construct(
        public string $companyName,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
        #[MapInputName('address_2')]
        public ?string $address2 = null,
        public ?string $website = null,
        public ?string $facebookLink = null,
        public ?string $instagramLink = null,
        public ?string $linkedinLink = null,
        public ?string $twitterLink = null,
        public ?string $tiktokLink = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            // E.164 from the shared PhoneField; accepts any valid international number.
            'phone' => ['nullable', 'string', 'max:20', 'phone:INTERNATIONAL'],
            'address' => ['nullable', 'string', 'max:1000'],
            'address_2' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'facebook_link' => ['nullable', 'url', 'max:255'],
            'instagram_link' => ['nullable', 'url', 'max:255'],
            'linkedin_link' => ['nullable', 'url', 'max:255'],
            'twitter_link' => ['nullable', 'url', 'max:255'],
            'tiktok_link' => ['nullable', 'url', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}

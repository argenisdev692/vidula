<?php

declare(strict_types=1);

namespace Modules\CompanyData\Domain\ValueObjects;

final readonly class SocialLinks
{
    public function __construct(
        public ?string $facebook = null,
        public ?string $instagram = null,
        public ?string $linkedin = null,
        public ?string $twitter = null,
        public ?string $website = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'facebook' => $this->facebook,
            'instagram' => $this->instagram,
            'linkedin' => $this->linkedin,
            'twitter' => $this->twitter,
            'website' => $this->website,
        ];
    }
}

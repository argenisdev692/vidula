<?php

declare(strict_types=1);

namespace Modules\Company\Domain\Events;

/**
 * Raised when the company record's ISO country code is changed through the admin
 * settings screen. The Availability module listens to rebuild national holidays
 * for the new country.
 */
final readonly class CompanyCountryChanged
{
    public function __construct(
        public ?string $previousCountryCode,
        public ?string $currentCountryCode,
    ) {}
}

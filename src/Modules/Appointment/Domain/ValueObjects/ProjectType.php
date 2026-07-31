<?php

declare(strict_types=1);

namespace Modules\Appointment\Domain\ValueObjects;

enum ProjectType: string
{
    case NewWebsite = 'new_website';

    case Redesign = 'redesign';

    case Ecommerce = 'ecommerce';

    case LandingPage = 'landing_page';

    case Maintenance = 'maintenance';

    case WebApp = 'web_app';

    case Other = 'other';
}

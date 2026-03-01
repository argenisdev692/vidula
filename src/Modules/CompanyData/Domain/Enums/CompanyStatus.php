<?php

declare(strict_types=1);

namespace Modules\CompanyData\Domain\Enums;

enum CompanyStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
}

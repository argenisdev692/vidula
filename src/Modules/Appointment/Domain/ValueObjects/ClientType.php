<?php

declare(strict_types=1);

namespace Modules\Appointment\Domain\ValueObjects;

enum ClientType: string
{
    case Company = 'company';

    case Individual = 'individual';
}

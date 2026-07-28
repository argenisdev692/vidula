<?php

declare(strict_types=1);

namespace Modules\Enrollments\Domain\Enums;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Completed = 'completed';
    case Dropped = 'dropped';
}

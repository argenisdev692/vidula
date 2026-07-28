<?php

declare(strict_types=1);

namespace Modules\Enrollments\Domain\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case Justified = 'justified';
}

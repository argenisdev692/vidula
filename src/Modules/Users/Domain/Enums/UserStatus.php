<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Enums;

/**
 * UserStatus — Domain State
 *
 * Tracks the status of a user within the system.
 */
enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Banned = 'banned';
    case Deleted = 'deleted';
    case PendingSetup = 'pending_setup';
}

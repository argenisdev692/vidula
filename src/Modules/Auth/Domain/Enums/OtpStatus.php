<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Enums;

/**
 * OtpStatus — Tracks the lifecycle of an OTP code.
 */
enum OtpStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Expired = 'expired';
    case Revoked = 'revoked';
}

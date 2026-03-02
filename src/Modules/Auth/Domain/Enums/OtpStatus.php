<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Enums;

/**
 * OtpStatus — Tracks the lifecycle of an OTP code with PHP 8.5 enhancements.
 * 
 * Features:
 * - Helper methods with #[\NoDiscard]
 * - State checking methods
 * - User-friendly labels
 */
enum OtpStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Expired = 'expired';
    case Revoked = 'revoked';

    #[\NoDiscard]
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Verification',
            self::Verified => 'Verified',
            self::Expired => 'Expired',
            self::Revoked => 'Revoked',
        };
    }

    #[\NoDiscard]
    public function description(): string
    {
        return match ($this) {
            self::Pending => 'OTP code is waiting to be verified',
            self::Verified => 'OTP code has been successfully verified',
            self::Expired => 'OTP code has expired and cannot be used',
            self::Revoked => 'OTP code has been manually revoked',
        };
    }

    #[\NoDiscard]
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Verified => 'success',
            self::Expired => 'danger',
            self::Revoked => 'secondary',
        };
    }

    public function isValid(): bool
    {
        return $this === self::Pending;
    }

    public function canResend(): bool
    {
        return in_array($this, [self::Expired, self::Revoked]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Verified, self::Revoked]);
    }
}

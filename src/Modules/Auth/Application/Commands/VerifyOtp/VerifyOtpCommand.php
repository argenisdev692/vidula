<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands\VerifyOtp;

/**
 * VerifyOtpCommand — Request to verify an OTP and authenticate.
 */
final readonly class VerifyOtpCommand
{
    public function __construct(
        public string $identifier,
        public string $code,
        public string $ipAddress,
        public string $userAgent,
    ) {
    }
}

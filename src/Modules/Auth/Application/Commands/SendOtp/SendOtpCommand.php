<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands\SendOtp;

/**
 * SendOtpCommand — Request to generate and send an OTP.
 */
readonly class SendOtpCommand
{
    public function __construct(
        public string $identifier,
    ) {
    }
}

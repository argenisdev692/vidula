<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Exceptions;

final class InvalidOtpException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('The provided OTP code is invalid or has expired.');
    }
}

<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Exceptions;

final class InvalidCredentialsException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('The provided credentials are incorrect.');
    }
}

<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Exceptions;

use RuntimeException;

final class SocialAuthException extends RuntimeException
{
    public static function linkRefused(): self
    {
        return new self(
            'This email is already registered. Please sign in with your password first, then link the provider from your security settings.'
        );
    }

    public static function unsupportedProvider(string $provider): self
    {
        return new self("Unsupported social provider [{$provider}].");
    }

    public static function noEmailProvided(): self
    {
        return new self('The social provider did not return an email address, which is required to create an account.');
    }
}

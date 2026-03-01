<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

/**
 * InvalidAvatarException
 */
final class InvalidAvatarException extends DomainException
{
    public static function invalidFormat(string $format): self
    {
        return new self("Invalid avatar format: {$format}. Only JPEG, PNG and WEBP are allowed.");
    }

    public static function tooLarge(int $size): self
    {
        return new self("Avatar is too large: {$size} bytes. Max 2MB allowed.");
    }
}

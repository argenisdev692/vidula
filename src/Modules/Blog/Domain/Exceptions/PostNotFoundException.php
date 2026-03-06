<?php

declare(strict_types=1);

namespace Modules\Blog\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

final class PostNotFoundException extends DomainException
{
    public static function forUuid(string $uuid): self
    {
        return new self("Post with UUID [{$uuid}] not found.");
    }
}

<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

final class ProductNotFoundException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("Product with ID [{$id}] not found.");
    }

    public static function forSlug(string $slug): self
    {
        return new self("Product with slug [{$slug}] not found.");
    }
}

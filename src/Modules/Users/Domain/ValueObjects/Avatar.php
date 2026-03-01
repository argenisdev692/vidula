<?php

declare(strict_types=1);

namespace Modules\Users\Domain\ValueObjects;

/**
 * Avatar — Immutable Value Object
 */
final readonly class Avatar
{
    public function __construct(
        public ?string $path
    ) {
    }

    public function url(): ?string
    {
        return $this->path ? "/storage/{$this->path}" : null;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Users\Domain\ValueObjects;

/**
 * Bio — Immutable Value Object
 */
final readonly class Bio
{
    public function __construct(
        public ?string $content
    ) {
    }

    #[\NoDiscard]
    public function excerpt(int $length = 100): string
    {
        if (null === $this->content) {
            return '';
        }

        return mb_strlen($this->content) <= $length
            ? $this->content
            : mb_substr($this->content, 0, $length) . '...';
    }
}

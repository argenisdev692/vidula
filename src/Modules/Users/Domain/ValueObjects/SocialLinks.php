<?php

declare(strict_types=1);

namespace Modules\Users\Domain\ValueObjects;

/**
 * SocialLinks — Immutable Value Object
 */
final readonly class SocialLinks
{
    public function __construct(
        public ?string $twitter = null,
        public ?string $linkedin = null,
        public ?string $github = null,
        public ?string $website = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'twitter' => $this->twitter,
            'linkedin' => $this->linkedin,
            'github' => $this->github,
            'website' => $this->website,
        ];
    }
}

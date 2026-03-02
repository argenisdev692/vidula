<?php

declare(strict_types=1);

namespace Modules\Users\Domain\ValueObjects;

use Uri\WhatWg\Url;

/**
 * SocialLinks — Immutable Value Object with URL validation
 */
final readonly class SocialLinks
{
    public function __construct(
        public ?string $twitter = null,
        public ?string $linkedin = null,
        public ?string $github = null,
        public ?string $website = null
    ) {
        $this->validateUrls();
    }

    /**
     * Validate all URLs using PHP 8.5 URI extension
     */
    private function validateUrls(): void
    {
        $urls = [
            'twitter' => $this->twitter,
            'linkedin' => $this->linkedin,
            'github' => $this->github,
            'website' => $this->website,
        ];

        foreach ($urls as $field => $url) {
            if ($url !== null && $url !== '') {
                try {
                    new Url($url);
                } catch (\Exception $e) {
                    throw new \InvalidArgumentException(
                        "Invalid URL for {$field}: {$url}. " . $e->getMessage()
                    );
                }
            }
        }
    }

    #[\NoDiscard]
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

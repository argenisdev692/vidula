<?php

declare(strict_types=1);

namespace Modules\Clients\Domain\ValueObjects;

use Uri\WhatWg\Url;

/**
 * SocialLinks Value Object with URI Extension validation
 * 
 * Property Hooks were proposed but NOT included in PHP 8.5 final release.
 * Using constructor validation with PHP 8.5 URI Extension instead.
 */
final readonly class SocialLinks
{
    public ?string $facebook;
    public ?string $instagram;
    public ?string $linkedin;
    public ?string $twitter;
    public ?string $website;

    public function __construct(
        ?string $facebook = null,
        ?string $instagram = null,
        ?string $linkedin = null,
        ?string $twitter = null,
        ?string $website = null
    ) {
        $this->facebook = $this->validateAndNormalizeUrl($facebook, 'facebook');
        $this->instagram = $this->validateAndNormalizeUrl($instagram, 'instagram');
        $this->linkedin = $this->validateAndNormalizeUrl($linkedin, 'linkedin');
        $this->twitter = $this->validateAndNormalizeUrl($twitter, 'twitter');
        $this->website = $this->validateAndNormalizeUrl($website, 'website');
    }

    /**
     * Validate URL using PHP 8.5 URI Extension
     */
    private function validateAndNormalizeUrl(?string $url, string $field): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        try {
            $urlObject = new Url($url);
            return $urlObject->toString();
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                "Invalid URL for {$field}: {$url}. " . $e->getMessage()
            );
        }
    }

    #[\NoDiscard]
    public function toArray(): array
    {
        return [
            'facebook' => $this->facebook,
            'instagram' => $this->instagram,
            'linkedin' => $this->linkedin,
            'twitter' => $this->twitter,
            'website' => $this->website,
        ];
    }
}


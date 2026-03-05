<?php

declare(strict_types=1);

namespace Modules\CompanyData\Domain\ValueObjects;

final class SocialLinks
{
    public function __construct(
        public private(set) ?string $facebook = null {
            set {
                $this->facebook = self::validateUrl('Facebook', $value);
            }
        },
        public private(set) ?string $instagram = null {
            set {
                $this->instagram = self::validateUrl('Instagram', $value);
            }
        },
        public private(set) ?string $linkedin = null {
            set {
                $this->linkedin = self::validateUrl('LinkedIn', $value);
            }
        },
        public private(set) ?string $twitter = null {
            set {
                $this->twitter = self::validateUrl('Twitter', $value);
            }
        },
        public private(set) ?string $website = null {
            set {
                $this->website = self::validateUrl('website', $value);
            }
        },
    ) {
    }

    private static function validateUrl(string $name, ?string $url): ?string
    {
        if ($url !== null && $url !== '') {
            try {
                new \Uri\WhatWg\Url($url);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException("Invalid {$name} URL: {$url}", previous: $e);
            }
        }
        return $url;
    }

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

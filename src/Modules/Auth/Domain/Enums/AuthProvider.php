<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Enums;

/**
 * AuthProvider — Backed enum for OAuth/login providers.
 */
enum AuthProvider: string
{
    case Email = 'email';
    case Google = 'google';
    case Github = 'github';
    case Facebook = 'facebook';
    case Microsoft = 'microsoft';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email & Password',
            self::Google => 'Google',
            self::Github => 'GitHub',
            self::Facebook => 'Facebook',
            self::Microsoft => 'Microsoft',
        };
    }
}

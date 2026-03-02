<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Enums;

/**
 * AuthProvider — Backed enum for OAuth/login providers with PHP 8.5 enhancements.
 * 
 * Features:
 * - Helper methods with #[\NoDiscard]
 * - Type checking methods
 * - Icon mapping for UI
 */
enum AuthProvider: string
{
    case Email = 'email';
    case Google = 'google';
    case Github = 'github';
    case Facebook = 'facebook';
    case Microsoft = 'microsoft';
    case Otp = 'otp';

    #[\NoDiscard]
    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email & Password',
            self::Google => 'Google',
            self::Github => 'GitHub',
            self::Facebook => 'Facebook',
            self::Microsoft => 'Microsoft',
            self::Otp => 'One-Time Password',
        };
    }

    #[\NoDiscard]
    public function icon(): string
    {
        return match ($this) {
            self::Email => 'envelope',
            self::Google => 'google',
            self::Github => 'github',
            self::Facebook => 'facebook',
            self::Microsoft => 'microsoft',
            self::Otp => 'key',
        };
    }

    #[\NoDiscard]
    public function description(): string
    {
        return match ($this) {
            self::Email => 'Sign in with your email and password',
            self::Google => 'Sign in with your Google account',
            self::Github => 'Sign in with your GitHub account',
            self::Facebook => 'Sign in with your Facebook account',
            self::Microsoft => 'Sign in with your Microsoft account',
            self::Otp => 'Sign in with a one-time password sent to your email or phone',
        };
    }

    public function requiresPassword(): bool
    {
        return $this === self::Email;
    }

    public function isOAuth(): bool
    {
        return in_array($this, [self::Google, self::Github, self::Facebook, self::Microsoft]);
    }

    public function isPasswordless(): bool
    {
        return $this === self::Otp;
    }
}

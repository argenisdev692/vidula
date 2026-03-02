<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit\Domain\Enums;

use Modules\Auth\Domain\Enums\AuthProvider;
use PHPUnit\Framework\TestCase;

/**
 * AuthProviderTest — Tests for AuthProvider enum with PHP 8.5 methods.
 */
final class AuthProviderTest extends TestCase
{
    public function test_has_correct_values(): void
    {
        $this->assertEquals('email', AuthProvider::Email->value);
        $this->assertEquals('google', AuthProvider::Google->value);
        $this->assertEquals('github', AuthProvider::Github->value);
        $this->assertEquals('otp', AuthProvider::Otp->value);
    }

    public function test_returns_correct_labels(): void
    {
        $this->assertEquals('Email & Password', AuthProvider::Email->label());
        $this->assertEquals('Google', AuthProvider::Google->label());
        $this->assertEquals('GitHub', AuthProvider::Github->label());
        $this->assertEquals('One-Time Password', AuthProvider::Otp->label());
    }

    public function test_returns_correct_icons(): void
    {
        $this->assertEquals('envelope', AuthProvider::Email->icon());
        $this->assertEquals('google', AuthProvider::Google->icon());
        $this->assertEquals('github', AuthProvider::Github->icon());
        $this->assertEquals('key', AuthProvider::Otp->icon());
    }

    public function test_returns_correct_descriptions(): void
    {
        $this->assertStringContainsString('email and password', AuthProvider::Email->description());
        $this->assertStringContainsString('Google account', AuthProvider::Google->description());
    }

    public function test_requires_password_only_for_email(): void
    {
        $this->assertTrue(AuthProvider::Email->requiresPassword());
        $this->assertFalse(AuthProvider::Google->requiresPassword());
        $this->assertFalse(AuthProvider::Otp->requiresPassword());
    }

    public function test_identifies_oauth_providers(): void
    {
        $this->assertFalse(AuthProvider::Email->isOAuth());
        $this->assertTrue(AuthProvider::Google->isOAuth());
        $this->assertTrue(AuthProvider::Github->isOAuth());
        $this->assertFalse(AuthProvider::Otp->isOAuth());
    }

    public function test_identifies_passwordless_providers(): void
    {
        $this->assertFalse(AuthProvider::Email->isPasswordless());
        $this->assertFalse(AuthProvider::Google->isPasswordless());
        $this->assertTrue(AuthProvider::Otp->isPasswordless());
    }
}

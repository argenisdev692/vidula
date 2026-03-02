<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit\Domain\ValueObjects;

use Modules\Auth\Domain\ValueObjects\Password;
use PHPUnit\Framework\TestCase;

/**
 * PasswordTest — Tests for Password value object with PHP 8.5 property hooks.
 */
final class PasswordTest extends TestCase
{
    public function test_creates_valid_password(): void
    {
        $password = Password::fromPlainText('SecurePass123');
        
        $this->assertInstanceOf(Password::class, $password);
    }

    public function test_throws_exception_for_short_password(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at least 8 characters');
        
        Password::fromPlainText('Short1');
    }

    public function test_throws_exception_for_password_without_uppercase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('uppercase letter');
        
        Password::fromPlainText('securepass123');
    }

    public function test_throws_exception_for_password_without_lowercase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('lowercase letter');
        
        Password::fromPlainText('SECUREPASS123');
    }

    public function test_throws_exception_for_password_without_number(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('number');
        
        Password::fromPlainText('SecurePassword');
    }

    public function test_hashes_password(): void
    {
        $password = Password::fromPlainText('SecurePass123');
        $hashed = $password->hash();
        
        $this->assertNotEquals('SecurePass123', $hashed);
        $this->assertStringStartsWith('$argon2id$', $hashed);
    }

    public function test_verifies_password(): void
    {
        $password = Password::fromPlainText('SecurePass123');
        $hashed = $password->hash();
        
        $this->assertTrue($password->verify($hashed));
    }

    public function test_checks_complexity_requirements(): void
    {
        $validPassword = Password::fromPlainText('SecurePass123');
        
        $this->assertTrue($validPassword->meetsComplexityRequirements());
    }

    public function test_calculates_password_strength(): void
    {
        $weakPassword = Password::fromPlainText('Password1');
        $mediumPassword = Password::fromPlainText('SecurePass123');
        $strongPassword = Password::fromPlainText('SecureP@ss123!');
        
        $this->assertEquals('weak', $weakPassword->strength());
        $this->assertEquals('medium', $mediumPassword->strength());
        $this->assertEquals('strong', $strongPassword->strength());
    }
}

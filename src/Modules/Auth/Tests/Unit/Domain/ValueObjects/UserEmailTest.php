<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit\Domain\ValueObjects;

use Modules\Auth\Domain\ValueObjects\UserEmail;
use PHPUnit\Framework\TestCase;

/**
 * UserEmailTest — Tests for UserEmail value object with PHP 8.5 property hooks.
 */
final class UserEmailTest extends TestCase
{
    public function test_creates_valid_email(): void
    {
        $email = new UserEmail('test@example.com');
        
        $this->assertEquals('test@example.com', $email->value);
    }

    public function test_normalizes_email_to_lowercase(): void
    {
        $email = new UserEmail('TEST@EXAMPLE.COM');
        
        $this->assertEquals('test@example.com', $email->value);
    }

    public function test_trims_whitespace(): void
    {
        $email = new UserEmail('  test@example.com  ');
        
        $this->assertEquals('test@example.com', $email->value);
    }

    public function test_throws_exception_for_invalid_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address');
        
        new UserEmail('invalid-email');
    }

    public function test_extracts_domain(): void
    {
        $email = new UserEmail('test@example.com');
        
        $this->assertEquals('example.com', $email->domain());
    }

    public function test_equals_compares_emails(): void
    {
        $email1 = new UserEmail('test@example.com');
        $email2 = new UserEmail('TEST@EXAMPLE.COM');
        $email3 = new UserEmail('other@example.com');
        
        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }

    public function test_converts_to_string(): void
    {
        $email = new UserEmail('test@example.com');
        
        $this->assertEquals('test@example.com', (string) $email);
    }
}

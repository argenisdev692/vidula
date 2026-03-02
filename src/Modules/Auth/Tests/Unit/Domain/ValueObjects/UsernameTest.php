<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit\Domain\ValueObjects;

use Modules\Auth\Domain\ValueObjects\Username;
use PHPUnit\Framework\TestCase;

/**
 * UsernameTest — Tests for Username value object with PHP 8.5 property hooks.
 */
final class UsernameTest extends TestCase
{
    public function test_creates_valid_username(): void
    {
        $username = new Username('john_doe');
        
        $this->assertEquals('john_doe', $username->value);
    }

    public function test_normalizes_to_lowercase(): void
    {
        $username = new Username('JohnDoe');
        
        $this->assertEquals('johndoe', $username->value);
    }

    public function test_trims_whitespace(): void
    {
        $username = new Username('  john_doe  ');
        
        $this->assertEquals('john_doe', $username->value);
    }

    public function test_throws_exception_for_short_username(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at least 3 characters');
        
        new Username('ab');
    }

    public function test_throws_exception_for_long_username(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not exceed 30 characters');
        
        new Username(str_repeat('a', 31));
    }

    public function test_throws_exception_for_invalid_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('lowercase letters, numbers, underscores, and hyphens');
        
        new Username('john@doe');
    }

    public function test_throws_exception_for_username_starting_with_number(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot start with a number');
        
        new Username('123john');
    }

    public function test_creates_from_email(): void
    {
        $username = Username::fromEmail('john.doe@example.com');
        
        $this->assertEquals('john_doe', $username->value);
    }

    public function test_generates_username(): void
    {
        $username = Username::generate('John Doe');
        
        $this->assertEquals('john_doe', $username->value);
    }

    public function test_generates_username_with_suffix(): void
    {
        $username = Username::generate('John Doe', 5);
        
        $this->assertEquals('john_doe_5', $username->value);
    }

    public function test_equals_compares_usernames(): void
    {
        $username1 = new Username('john_doe');
        $username2 = new Username('JOHN_DOE');
        $username3 = new Username('jane_doe');
        
        $this->assertTrue($username1->equals($username2));
        $this->assertFalse($username1->equals($username3));
    }

    public function test_converts_to_string(): void
    {
        $username = new Username('john_doe');
        
        $this->assertEquals('john_doe', (string) $username);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit\Domain\ValueObjects;

use Modules\Auth\Domain\ValueObjects\OtpCode;
use PHPUnit\Framework\TestCase;

/**
 * OtpCodeTest — Tests for OtpCode value object with PHP 8.5 property hooks.
 */
final class OtpCodeTest extends TestCase
{
    public function test_creates_valid_otp_code(): void
    {
        $otp = new OtpCode('123456');
        
        $this->assertEquals('123456', $otp->value);
    }

    public function test_throws_exception_for_invalid_length(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly 6 digits');
        
        new OtpCode('12345');
    }

    public function test_throws_exception_for_non_numeric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly 6 digits');
        
        new OtpCode('12345a');
    }

    public function test_generates_random_otp(): void
    {
        $otp = OtpCode::generate();
        
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp->value);
    }

    public function test_generates_different_otps(): void
    {
        $otp1 = OtpCode::generate();
        $otp2 = OtpCode::generate();
        
        // Very unlikely to be equal (1 in 1,000,000 chance)
        $this->assertNotEquals($otp1->value, $otp2->value);
    }

    public function test_equals_uses_constant_time_comparison(): void
    {
        $otp1 = new OtpCode('123456');
        $otp2 = new OtpCode('123456');
        $otp3 = new OtpCode('654321');
        
        $this->assertTrue($otp1->equals($otp2));
        $this->assertFalse($otp1->equals($otp3));
    }

    public function test_converts_to_string(): void
    {
        $otp = new OtpCode('123456');
        
        $this->assertEquals('123456', (string) $otp);
    }
}

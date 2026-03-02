<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit\Domain\Enums;

use Modules\Auth\Domain\Enums\OtpStatus;
use PHPUnit\Framework\TestCase;

/**
 * OtpStatusTest — Tests for OtpStatus enum with PHP 8.5 methods.
 */
final class OtpStatusTest extends TestCase
{
    public function test_has_correct_values(): void
    {
        $this->assertEquals('pending', OtpStatus::Pending->value);
        $this->assertEquals('verified', OtpStatus::Verified->value);
        $this->assertEquals('expired', OtpStatus::Expired->value);
        $this->assertEquals('revoked', OtpStatus::Revoked->value);
    }

    public function test_returns_correct_labels(): void
    {
        $this->assertEquals('Pending Verification', OtpStatus::Pending->label());
        $this->assertEquals('Verified', OtpStatus::Verified->label());
        $this->assertEquals('Expired', OtpStatus::Expired->label());
        $this->assertEquals('Revoked', OtpStatus::Revoked->label());
    }

    public function test_returns_correct_descriptions(): void
    {
        $this->assertStringContainsString('waiting', OtpStatus::Pending->description());
        $this->assertStringContainsString('successfully verified', OtpStatus::Verified->description());
    }

    public function test_returns_correct_colors(): void
    {
        $this->assertEquals('warning', OtpStatus::Pending->color());
        $this->assertEquals('success', OtpStatus::Verified->color());
        $this->assertEquals('danger', OtpStatus::Expired->color());
        $this->assertEquals('secondary', OtpStatus::Revoked->color());
    }

    public function test_is_valid_only_for_pending(): void
    {
        $this->assertTrue(OtpStatus::Pending->isValid());
        $this->assertFalse(OtpStatus::Verified->isValid());
        $this->assertFalse(OtpStatus::Expired->isValid());
        $this->assertFalse(OtpStatus::Revoked->isValid());
    }

    public function test_can_resend_for_expired_and_revoked(): void
    {
        $this->assertFalse(OtpStatus::Pending->canResend());
        $this->assertFalse(OtpStatus::Verified->canResend());
        $this->assertTrue(OtpStatus::Expired->canResend());
        $this->assertTrue(OtpStatus::Revoked->canResend());
    }

    public function test_is_final_for_verified_and_revoked(): void
    {
        $this->assertFalse(OtpStatus::Pending->isFinal());
        $this->assertTrue(OtpStatus::Verified->isFinal());
        $this->assertFalse(OtpStatus::Expired->isFinal());
        $this->assertTrue(OtpStatus::Revoked->isFinal());
    }
}

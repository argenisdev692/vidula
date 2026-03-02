<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit\Domain\ValueObjects;

use Modules\Auth\Domain\ValueObjects\IpAddress;
use PHPUnit\Framework\TestCase;

/**
 * IpAddressTest — Tests for IpAddress value object with PHP 8.5 property hooks.
 */
final class IpAddressTest extends TestCase
{
    public function test_creates_valid_ipv4_address(): void
    {
        $ip = new IpAddress('192.168.1.1');
        
        $this->assertEquals('192.168.1.1', $ip->value);
    }

    public function test_creates_valid_ipv6_address(): void
    {
        $ip = new IpAddress('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
        
        $this->assertEquals('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $ip->value);
    }

    public function test_throws_exception_for_invalid_ip(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid IP address');
        
        new IpAddress('invalid-ip');
    }

    public function test_detects_ipv4(): void
    {
        $ip = new IpAddress('192.168.1.1');
        
        $this->assertTrue($ip->isIPv4());
        $this->assertFalse($ip->isIPv6());
    }

    public function test_detects_ipv6(): void
    {
        $ip = new IpAddress('2001:0db8:85a3::8a2e:0370:7334');
        
        $this->assertTrue($ip->isIPv6());
        $this->assertFalse($ip->isIPv4());
    }

    public function test_equals_compares_ip_addresses(): void
    {
        $ip1 = new IpAddress('192.168.1.1');
        $ip2 = new IpAddress('192.168.1.1');
        $ip3 = new IpAddress('192.168.1.2');
        
        $this->assertTrue($ip1->equals($ip2));
        $this->assertFalse($ip1->equals($ip3));
    }

    public function test_converts_to_string(): void
    {
        $ip = new IpAddress('192.168.1.1');
        
        $this->assertEquals('192.168.1.1', (string) $ip);
    }
}

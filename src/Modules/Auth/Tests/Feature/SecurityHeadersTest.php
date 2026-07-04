<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Tests\TestCase;

final class SecurityHeadersTest extends TestCase
{
    public function test_responses_carry_secure_headers_and_csp(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("'nonce-", $response->headers->get('Content-Security-Policy'));
    }
}

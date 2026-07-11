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

    public function test_api_docs_page_receives_a_scoped_csp_that_allows_stoplight_assets(): void
    {
        // Access may be 403 (RestrictedDocsAccess outside local), but the route
        // still matches the web group, so SecurityHeaders sets the scoped CSP.
        $csp = $this->get('/docs/api')->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        // Stoplight Elements bundle + inline boot script must be permitted here...
        $this->assertStringContainsString('https://unpkg.com', $csp);
        // ...and this page is deliberately NOT nonce-gated (vendor view).
        $this->assertStringNotContainsString("'nonce-", $csp);
    }
}

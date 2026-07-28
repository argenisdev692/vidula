<?php

declare(strict_types=1);

namespace Modules\Services\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Services\Application\Queries\ListPublicServicesHandler;

/**
 * Unauthenticated feed for the landing page select input: `is_active` services
 * only, with an explicit column allowlist applied at the repository
 * (BACKEND-PHP §4.1 + OWASP §12 property-level authorization — never a raw
 * `Model::all()`). Reachable by anonymous internet traffic, hence the tighter
 * throttle at the route and no `auth`/`permission` middleware.
 * Scramble documents via return types + MiddlewareAuthSecurityStrategy
 * (no auth middleware → public `security: []`) — no manual `@OA\*` annotations.
 */
final readonly class PublicServiceController
{
    /**
     * List active services.
     *
     * Returns the full list of active services (name, slug, description) for
     * the landing page's service `<select>` input. Not paginated — the catalog
     * is a small, bounded set.
     */
    public function index(ListPublicServicesHandler $list): JsonResponse
    {
        return response()->json(['data' => $list->handle()]);
    }
}

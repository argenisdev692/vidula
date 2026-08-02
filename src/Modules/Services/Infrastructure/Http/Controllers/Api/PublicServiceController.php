<?php

declare(strict_types=1);

namespace Modules\Services\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Services\Application\Queries\ListPublicServicesHandler;
use Modules\Services\Application\ReadModels\ServicePublicReadModel;

/**
 * Unauthenticated feed for the landing page select input: `is_active` services
 * only, shaped by {@see ServicePublicReadModel} (BACKEND-PHP §4.1 + OWASP §12
 * property-level authorization). Reachable by anonymous internet traffic, hence
 * the tighter throttle at the route and no `auth`/`permission` middleware.
 * Scramble documents via {@see ServicePublicReadModel} array shape on
 * {@see index()} — no manual `@OA\*` annotations.
 */
final readonly class PublicServiceController
{
    /**
     * List active services.
     *
     * Returns the full list of active services (name, slug, description) for
     * the landing page's service `<select>` input. Not paginated — the catalog
     * is a small, bounded set.
     *
     * @return JsonResponse<array{data: list<array{uuid: string, name: string, slug: string, description: string|null, sort_order: int}>}>
     */
    public function index(ListPublicServicesHandler $list): JsonResponse
    {
        return response()->json(['data' => $list->handle()]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Portfolio\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\Portfolio\Application\Queries\ListPublicPortfoliosHandler;
use Modules\Portfolio\Application\ReadModels\PortfolioPublicReadModel;
use Spatie\LaravelData\PaginatedDataCollection;

/**
 * Unauthenticated landing-page feed: `is_public` portfolios only, shaped by the
 * {@see PortfolioPublicReadModel} allowlist (BACKEND-PHP §4.1 + OWASP §12
 * property-level authorization — never a raw model serialization). Reachable by
 * anonymous internet traffic, hence the tighter throttle at the route and no
 * `auth`/`permission` middleware.
 */
final readonly class PublicPortfolioController
{
    /**
     * List public portfolios.
     *
     * Paginated feed of published projects for the landing page. `per_page` is
     * capped at 100 to bound resource consumption (OWASP API4).
     *
     * @return PaginatedDataCollection<int, PortfolioPublicReadModel>
     */
    public function index(Request $request, ListPublicPortfoliosHandler $list): PaginatedDataCollection
    {
        return $list->handle(min(max($request->integer('per_page', 15), 1), 100));
    }
}

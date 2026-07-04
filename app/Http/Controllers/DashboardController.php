<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Authenticated application home. Renders the Vue `Dashboard` page (page header,
 * stat cards, claims chart and recent-activity feed). Kept invokable so
 * `route:cache` can serialize it for production.
 */
final class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard');
    }
}

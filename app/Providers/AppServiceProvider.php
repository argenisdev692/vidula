<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Shared\Infrastructure\Company\CompanyProfile;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Per-route buckets so Astro builds (many parallel public GETs) do not
        // share one throttle:60,1 counter across every landing endpoint.
        RateLimiter::for('landing-public', function (Request $request) {
            $routeKey = $request->route()?->getName() ?? $request->path();
            $identity = $request->ip() ?? 'guest';

            try {
                $userId = $request->user()?->getAuthIdentifier();
                if ($userId !== null && $userId !== '') {
                    $identity = (string) $userId;
                }
            } catch (Throwable) {
                // Auth guard misconfigured mid-request — still rate-limit by IP.
            }

            return Limit::perMinute(120)->by($routeKey.'|'.$identity);
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Password policy: 8+ chars, mixed case, a number and a symbol.
        // Consumed everywhere via Password::default().
        Password::defaults(fn (): Password => Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols());

        // N+1 / attribute-strictness guard (BACKEND-PHP §4.1 #1). Scoped to the
        // `local` dev environment (crash on violation) so it surfaces N+1s while
        // developing; left off under `testing` to avoid tripping on Spatie
        // Permission's lazy role-relation loading, and log-only in production.
        Model::shouldBeStrict($this->app->environment('local'));

        if ($this->app->isProduction()) {
            Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
                logger()->warning("Lazy loading [{$relation}] on [".$model::class.'].');
            });
        }

        // Inject the default document title (DB brand + env tagline) into the
        // Inertia root view only — runs on full-page loads, not XHR visits.
        View::composer('app', static function (ViewContract $view): void {
            $view->with('documentTitle', CompanyProfile::documentTitle());
        });
    }
}

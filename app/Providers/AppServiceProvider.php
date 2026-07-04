<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Enterprise password policy (prompt §5): 12+ chars, mixed case, a
        // number and a symbol. Consumed everywhere via Password::default().
        Password::defaults(fn (): Password => Password::min(12)
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
    }
}

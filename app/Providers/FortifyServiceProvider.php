<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;
use Modules\Auth\Infrastructure\Auth\TwoFactor\RedirectIfTwoFactorAuthenticatable;

class FortifyServiceProvider extends ServiceProvider
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
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        // Password reset uses the Auth module's OTP flow (PasswordResetController),
        // which reuses App\Actions\Fortify\ResetUserPassword directly.
        // Module action honours the 30-day trusted-device cookie (prompt §3).
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // The canonical login view is bound in Modules\Auth\AuthServiceProvider
        // (renders Auth/Login). Kept here as a safe fallback if that provider's
        // boot order ever changes.
        Fortify::loginView(fn () => Inertia::render('Auth/Login', ['canResetPassword' => true]));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        // Prompt §2: 2FA challenge — max 5 attempts / user / 5 minutes.
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinutes(5, 5)->by($request->session()->get('login.id'));
        });

        // Prompt §2: registration — max 3 per IP / hour.
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(3)->by((string) $request->ip());
        });

        $this->app->booted(function (): void {
            $route = Route::getRoutes()->getByName('register.store');

            if ($route === null) {
                return;
            }

            $middleware = array_values(array_filter(
                (array) ($route->action['middleware'] ?? []),
                static fn (mixed $m): bool => $m !== 'throttle:register',
            ));

            // Prepend so throttle runs BEFORE `guest`. Otherwise an authenticated
            // session (Fortify auto-login after register) gets RedirectIfAuthenticated
            // 302 and never consumes the limiter — tests and bots alike bypass it.
            $route->action['middleware'] = ['throttle:register', ...$middleware];
        });
    }
}

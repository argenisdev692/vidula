<?php

declare(strict_types=1);

namespace Modules\Auth\Providers;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\PasswordConfirmedResponse as PasswordConfirmedResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Fortify;
use Modules\Auth\Domain\Events\LoginFailed;
use Modules\Auth\Domain\Ports\AccountLockoutPort;
use Modules\Auth\Domain\Ports\GeoLocatorPort;
use Modules\Auth\Domain\Ports\LoginAttemptRepositoryPort;
use Modules\Auth\Infrastructure\Geo\CdnHeaderGeoLocator;
use Modules\Auth\Infrastructure\Http\Controllers\Web\AuthPageController;
use Modules\Auth\Infrastructure\Http\Responses\LoginResponse;
use Modules\Auth\Infrastructure\Http\Responses\PasswordConfirmedResponse;
use Modules\Auth\Infrastructure\Http\Responses\TwoFactorLoginResponse;
use Modules\Auth\Infrastructure\Listeners\LockoutWarningListener;
use Modules\Auth\Infrastructure\Listeners\NewDeviceListener;
use Modules\Auth\Infrastructure\Listeners\TwoFactorAuditListener;
use Modules\Auth\Infrastructure\Listeners\WebAuthenticationListener;
use Modules\Auth\Infrastructure\Lockout\CacheAccountLockoutAdapter;
use Modules\Auth\Infrastructure\Persistence\Repositories\EloquentLoginAttemptRepository;

final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LoginAttemptRepositoryPort::class, EloquentLoginAttemptRepository::class);
        $this->app->bind(AccountLockoutPort::class, CacheAccountLockoutAdapter::class);
        $this->app->bind(GeoLocatorPort::class, CdnHeaderGeoLocator::class);

        // Same-origin gate on Fortify post-auth redirects (blocks gmail.com / off-site intended).
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(PasswordConfirmedResponseContract::class, PasswordConfirmedResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, TwoFactorLoginResponse::class);
    }

    public function boot(): void
    {
        $this->registerApiRoutes();
        $this->registerWebRoutes();
        $this->registerInertiaAuthViews();
        $this->registerAuthEventListeners();
        $this->registerTwoFactorAuditListeners();
    }

    private function registerApiRoutes(): void
    {
        Route::middleware('api')->group(__DIR__.'/../Infrastructure/Routes/api.php');
    }

    private function registerWebRoutes(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');
    }

    /**
     * Bind Fortify's GET "view" routes to Inertia pages (the web controller for
     * Inertia). Fortify keeps ownership of the POST/PUT/DELETE auth actions.
     */
    private function registerInertiaAuthViews(): void
    {
        // Fortify's GET /login renders the Inertia login page (the two-panel hero
        // card). POST /login stays owned by Fortify.
        Fortify::loginView(fn (Request $request) => app(AuthPageController::class)->login($request));
        Fortify::registerView(fn () => app(AuthPageController::class)->register());
        // Password-reset GET pages are owned by the module (OTP flow), not Fortify
        // — see the /forgot-password + /reset-password routes in Routes/web.php.
        Fortify::verifyEmailView(fn (Request $request) => app(AuthPageController::class)->verifyEmail($request));
        Fortify::confirmPasswordView(fn () => app(AuthPageController::class)->confirmPassword());
        Fortify::twoFactorChallengeView(fn () => app(AuthPageController::class)->twoFactorChallenge());
    }

    /**
     * Bridge Laravel's session-guard auth events (fired by Fortify on the web
     * path) into the audit trail + lockout counter. Registered explicitly
     * because attribute auto-discovery only scans app/Listeners, not src/.
     */
    private function registerAuthEventListeners(): void
    {
        Event::listen(Login::class, [WebAuthenticationListener::class, 'onLogin']);
        Event::listen(Failed::class, [WebAuthenticationListener::class, 'onFailed']);
        Event::listen(Lockout::class, [WebAuthenticationListener::class, 'onLockout']);
        Event::listen(Logout::class, [WebAuthenticationListener::class, 'onLogout']);

        // Runs AFTER WebAuthenticationListener::onLogin so the current success
        // is already recorded when the new-device check counts prior sign-ins.
        Event::listen(Login::class, [NewDeviceListener::class, 'onLogin']);

        // English warning email + lock when the failure threshold is crossed.
        Event::listen(LoginFailed::class, [LockoutWarningListener::class, 'onLoginFailed']);
    }

    private function registerTwoFactorAuditListeners(): void
    {
        Event::listen(TwoFactorAuthenticationEnabled::class, [TwoFactorAuditListener::class, 'onEnabled']);
        Event::listen(TwoFactorAuthenticationConfirmed::class, [TwoFactorAuditListener::class, 'onConfirmed']);
        Event::listen(TwoFactorAuthenticationDisabled::class, [TwoFactorAuditListener::class, 'onDisabled']);
        Event::listen(RecoveryCodesGenerated::class, [TwoFactorAuditListener::class, 'onRecoveryCodesGenerated']);
        Event::listen(RecoveryCodeReplaced::class, [TwoFactorAuditListener::class, 'onRecoveryCodeReplaced']);
    }
}

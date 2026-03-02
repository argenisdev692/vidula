<?php

declare(strict_types=1);

namespace Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Auth\Domain\Ports\OtpServicePort;
use Modules\Auth\Domain\Ports\SocialiteRepositoryPort;
use Modules\Auth\Domain\Ports\UserRepositoryPort;
use Modules\Auth\Infrastructure\ExternalServices\Otp\CacheOtpAdapter;
use Modules\Auth\Infrastructure\Persistence\Repositories\EloquentSocialiteRepository;
use Modules\Auth\Infrastructure\Persistence\Repositories\EloquentUserRepository;

/**
 * AuthServiceProvider — Binds Auth context ports to their infrastructure adapters.
 *
 * ── Port → Adapter Bindings ─────────────────────────────
 * • UserRepositoryPort      → EloquentUserRepository
 * • SocialiteRepositoryPort → EloquentSocialiteRepository
 * • OtpServicePort          → CacheOtpAdapter
 *
 * ⚠️  A Port without a binding compiles silently but throws a
 *     fatal container resolution error at runtime.
 *
 * Must be registered in bootstrap/providers.php.
 */
final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Domain Ports → Infrastructure Adapters ──
        $this->app->bind(UserRepositoryPort::class, EloquentUserRepository::class);
        $this->app->bind(SocialiteRepositoryPort::class, EloquentSocialiteRepository::class);
        $this->app->bind(OtpServicePort::class, CacheOtpAdapter::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Infrastructure/Routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../Infrastructure/Routes/api.php');
    }
}

<?php

declare(strict_types=1);

namespace Modules\Blog\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;
use Modules\Blog\Domain\Ports\PostRepositoryPort;
use Modules\Blog\Infrastructure\Persistence\Repositories\EloquentBlogCategoryRepository;
use Modules\Blog\Infrastructure\Persistence\Repositories\EloquentPostRepository;

/**
 * BlogServiceProvider — Binds Blog context ports to their infrastructure adapters.
 */
final class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Domain Ports → Infrastructure Adapters ──
        $this->app->bind(BlogCategoryRepositoryPort::class, EloquentBlogCategoryRepository::class);
        $this->app->bind(PostRepositoryPort::class, EloquentPostRepository::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Blog\Infrastructure\CLI\PublishScheduledPostsCommand::class,
            ]);
        }

        // ── Context-specific Route Loading ──
        $this->registerWebRoutes();
    }

    private function registerWebRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix('blog-categories')
            ->group(__DIR__ . '/../Infrastructure/Routes/web.php');

        Route::middleware(['web', 'auth'])
            ->prefix('posts')
            ->group(__DIR__ . '/../Infrastructure/Routes/posts.php');
    }
}

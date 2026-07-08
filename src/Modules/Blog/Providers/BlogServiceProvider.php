<?php

declare(strict_types=1);

namespace Modules\Blog\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Blog\Domain\Ports\BlogCategoryRepositoryPort;
use Modules\Blog\Infrastructure\Persistence\Repositories\EloquentBlogCategoryRepository;

final class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BlogCategoryRepositoryPort::class, EloquentBlogCategoryRepository::class);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');
        Route::middleware('api')->prefix('api')->group(__DIR__.'/../Infrastructure/Routes/api.php');
    }
}

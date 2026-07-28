<?php

declare(strict_types=1);

namespace Modules\Products\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Products\Domain\Ports\ContentGenerationDispatcherPort;
use Modules\Products\Domain\Ports\ContentGenerationRepositoryPort;
use Modules\Products\Domain\Ports\CourseRendererPort;
use Modules\Products\Domain\Ports\ProductContentGeneratorPort;
use Modules\Products\Domain\Ports\ProductMaterialRepositoryPort;
use Modules\Products\Domain\Ports\ProductRepositoryPort;
use Modules\Products\Domain\Ports\ProductScriptRepositoryPort;
use Modules\Products\Domain\Ports\ZipPackagePort;
use Modules\Products\Infrastructure\Ai\LaravelAiProductGeneratorAdapter;
use Modules\Products\Infrastructure\Packaging\PhpZipPackageAdapter;
use Modules\Products\Infrastructure\Persistence\Repositories\EloquentContentGenerationRepository;
use Modules\Products\Infrastructure\Persistence\Repositories\EloquentProductMaterialRepository;
use Modules\Products\Infrastructure\Persistence\Repositories\EloquentProductRepository;
use Modules\Products\Infrastructure\Persistence\Repositories\EloquentProductScriptRepository;
use Modules\Products\Infrastructure\Queue\QueuedContentGenerationDispatcher;
use Modules\Products\Infrastructure\Rendering\MarkdownDomPdfCourseRenderer;

final class ProductsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../../config/products.php', 'products');

        $this->app->bind(ProductRepositoryPort::class, EloquentProductRepository::class);
        $this->app->bind(ContentGenerationRepositoryPort::class, EloquentContentGenerationRepository::class);
        $this->app->bind(ProductScriptRepositoryPort::class, EloquentProductScriptRepository::class);
        $this->app->bind(ProductMaterialRepositoryPort::class, EloquentProductMaterialRepository::class);
        $this->app->bind(ProductContentGeneratorPort::class, LaravelAiProductGeneratorAdapter::class);
        $this->app->bind(CourseRendererPort::class, MarkdownDomPdfCourseRenderer::class);
        $this->app->bind(ZipPackagePort::class, PhpZipPackageAdapter::class);
        $this->app->bind(ContentGenerationDispatcherPort::class, QueuedContentGenerationDispatcher::class);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');
        Route::middleware('api')->prefix('api')->group(__DIR__.'/../Infrastructure/Routes/api.php');
    }
}

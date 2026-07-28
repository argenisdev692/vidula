<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\AiResumeStudio\Domain\Ports\GithubEnrichmentRepositoryPort;
use Modules\AiResumeStudio\Domain\Ports\GithubPortfolioPort;
use Modules\AiResumeStudio\Domain\Ports\JobMatchRepositoryPort;
use Modules\AiResumeStudio\Domain\Ports\JobPageScraperPort;
use Modules\AiResumeStudio\Domain\Ports\JobSearchConfigRepositoryPort;
use Modules\AiResumeStudio\Domain\Ports\OutreachDraftRepositoryPort;
use Modules\AiResumeStudio\Domain\Ports\RefinedCvRepositoryPort;
use Modules\AiResumeStudio\Domain\Ports\StudioRunDispatcherPort;
use Modules\AiResumeStudio\Domain\Ports\StudioRunRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Console\Commands\RunDailyResumeStudioCommand;
use Modules\AiResumeStudio\Infrastructure\Integrations\FirecrawlJobPageScraperAdapter;
use Modules\AiResumeStudio\Infrastructure\Integrations\GithubPortfolioAdapter;
use Modules\AiResumeStudio\Infrastructure\Persistence\Repositories\EloquentGithubEnrichmentRepository;
use Modules\AiResumeStudio\Infrastructure\Persistence\Repositories\EloquentJobMatchRepository;
use Modules\AiResumeStudio\Infrastructure\Persistence\Repositories\EloquentJobSearchConfigRepository;
use Modules\AiResumeStudio\Infrastructure\Persistence\Repositories\EloquentOutreachDraftRepository;
use Modules\AiResumeStudio\Infrastructure\Persistence\Repositories\EloquentRefinedCvRepository;
use Modules\AiResumeStudio\Infrastructure\Persistence\Repositories\EloquentStudioRunRepository;
use Modules\AiResumeStudio\Infrastructure\Queue\QueuedStudioRunDispatcher;

final class AiResumeStudioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StudioRunRepositoryPort::class, EloquentStudioRunRepository::class);
        $this->app->bind(JobSearchConfigRepositoryPort::class, EloquentJobSearchConfigRepository::class);
        $this->app->bind(JobMatchRepositoryPort::class, EloquentJobMatchRepository::class);
        $this->app->bind(OutreachDraftRepositoryPort::class, EloquentOutreachDraftRepository::class);
        $this->app->bind(RefinedCvRepositoryPort::class, EloquentRefinedCvRepository::class);
        $this->app->bind(GithubEnrichmentRepositoryPort::class, EloquentGithubEnrichmentRepository::class);
        $this->app->bind(GithubPortfolioPort::class, GithubPortfolioAdapter::class);
        $this->app->bind(JobPageScraperPort::class, FirecrawlJobPageScraperAdapter::class);
        $this->app->bind(StudioRunDispatcherPort::class, QueuedStudioRunDispatcher::class);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([RunDailyResumeStudioCommand::class]);
        }
    }
}

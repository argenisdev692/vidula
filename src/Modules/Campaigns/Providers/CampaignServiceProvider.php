<?php

declare(strict_types=1);

namespace Modules\Campaigns\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Campaigns\Domain\Ports\CampaignGenerationDispatcherPort;
use Modules\Campaigns\Domain\Ports\CampaignGeneratorPort;
use Modules\Campaigns\Domain\Ports\CampaignIdeatorPort;
use Modules\Campaigns\Domain\Ports\CampaignRepositoryPort;
use Modules\Campaigns\Infrastructure\Ai\LaravelAiCampaignAssistantAdapter;
use Modules\Campaigns\Infrastructure\Console\Commands\PublishScheduledCampaignsCommand;
use Modules\Campaigns\Infrastructure\Persistence\Repositories\EloquentCampaignRepository;
use Modules\Campaigns\Infrastructure\Queue\QueuedCampaignGenerationDispatcher;

final class CampaignServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CampaignRepositoryPort::class, EloquentCampaignRepository::class);
        $this->app->bind(CampaignGenerationDispatcherPort::class, QueuedCampaignGenerationDispatcher::class);

        // Both AI ports resolve to the same adapter instance per request —
        // one Tavily research round-trip is shared if a caller ever needs
        // more than one during the same request (mirrors Post/SocialMedia).
        $this->app->singleton(LaravelAiCampaignAssistantAdapter::class);
        $this->app->bind(CampaignIdeatorPort::class, LaravelAiCampaignAssistantAdapter::class);
        $this->app->bind(CampaignGeneratorPort::class, LaravelAiCampaignAssistantAdapter::class);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');
        Route::middleware('api')->prefix('api')->group(__DIR__.'/../Infrastructure/Routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->commands([PublishScheduledCampaignsCommand::class]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\ActivityLog\Infrastructure\Console\Commands\ArchiveActivityLogsCommand;

final class ActivityLogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([ArchiveActivityLogsCommand::class]);
        }
    }
}

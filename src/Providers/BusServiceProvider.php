<?php

declare(strict_types=1);

namespace Src\Providers;

use Illuminate\Support\ServiceProvider;
use Shared\Application\Bus\Command\CommandBusInterface;
use Shared\Application\Bus\Command\SyncCommandBus;
use Shared\Application\Bus\Query\QueryBusInterface;
use Shared\Application\Bus\Query\SyncQueryBus;

final class BusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CommandBusInterface::class, SyncCommandBus::class);
        $this->app->singleton(QueryBusInterface::class, SyncQueryBus::class);
    }
}

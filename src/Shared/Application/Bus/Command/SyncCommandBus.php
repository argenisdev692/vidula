<?php

declare(strict_types=1);

namespace Shared\Application\Bus\Command;

use Illuminate\Support\Facades\Bus;

final class SyncCommandBus implements CommandBusInterface
{
    public function dispatch(object $command): void
    {
        Bus::dispatch($command);
    }

    public function map(array $map): void
    {
        Bus::map($map);
    }
}

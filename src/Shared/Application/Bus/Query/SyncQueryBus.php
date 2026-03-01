<?php

declare(strict_types=1);

namespace Shared\Application\Bus\Query;

use Illuminate\Support\Facades\Bus;

final class SyncQueryBus implements QueryBusInterface
{
    public function ask(object $query): mixed
    {
        return Bus::dispatch($query);
    }

    public function map(array $map): void
    {
        Bus::map($map);
    }
}

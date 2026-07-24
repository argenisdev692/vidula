<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;

final readonly class RestoreClientHandler
{
    public function __construct(private ClientRepositoryPort $clients) {}

    public function handle(string $uuid): bool
    {
        return DB::transaction(fn () => $this->clients->restore($uuid));
    }
}

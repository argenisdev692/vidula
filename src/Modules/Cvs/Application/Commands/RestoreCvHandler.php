<?php

declare(strict_types=1);

namespace Modules\Cvs\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Cvs\Domain\Ports\CvRepositoryPort;

final readonly class RestoreCvHandler
{
    public function __construct(private CvRepositoryPort $cvs) {}

    public function handle(string $uuid): bool
    {
        return DB::transaction(fn (): bool => $this->cvs->restore($uuid));
    }
}

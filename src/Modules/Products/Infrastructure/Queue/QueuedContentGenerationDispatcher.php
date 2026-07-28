<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Queue;

use Modules\Products\Domain\Ports\ContentGenerationDispatcherPort;

/**
 * @see ContentGenerationDispatcherPort
 */
final readonly class QueuedContentGenerationDispatcher implements ContentGenerationDispatcherPort
{
    public function dispatch(string $generationUuid, ?int $causerId = null): void
    {
        GenerateProductContentJob::dispatch($generationUuid, $causerId);
    }

    public function dispatchPackaging(string $generationUuid, ?int $causerId = null): void
    {
        BuildProductZipJob::dispatch($generationUuid, $causerId);
    }
}

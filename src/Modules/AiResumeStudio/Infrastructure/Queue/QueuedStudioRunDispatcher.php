<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Queue;

use Modules\AiResumeStudio\Domain\Ports\StudioRunDispatcherPort;

/**
 * @see StudioRunDispatcherPort
 */
final readonly class QueuedStudioRunDispatcher implements StudioRunDispatcherPort
{
    public function dispatch(string $studioRunUuid): void
    {
        ProcessStudioRunJob::dispatch($studioRunUuid);
    }
}

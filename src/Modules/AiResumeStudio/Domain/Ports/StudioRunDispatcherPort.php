<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Ports;

/**
 * Hands a persisted studio run to the async pipeline.
 *
 * Application depends on this instead of the queue Job so handlers stay
 * transport-agnostic (same split as Products ContentGenerationDispatcherPort).
 */
interface StudioRunDispatcherPort
{
    public function dispatch(string $studioRunUuid): void;
}

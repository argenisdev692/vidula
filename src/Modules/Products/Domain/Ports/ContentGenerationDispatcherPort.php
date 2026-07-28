<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Ports;

/**
 * Hands a persisted, still-pending generation to the async pipeline.
 *
 * The Application layer depends on this instead of the queue Job so the
 * command handler stays synchronous-testable and transport-agnostic — the
 * same split Campaigns uses for its quality loop.
 */
interface ContentGenerationDispatcherPort
{
    public function dispatch(string $generationUuid, ?int $causerId = null): void;

    /**
     * Re-render + re-zip an already generated product (US-5 "package" action).
     */
    public function dispatchPackaging(string $generationUuid, ?int $causerId = null): void;
}

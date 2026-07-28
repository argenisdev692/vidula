<?php

declare(strict_types=1);

namespace Modules\Products\Application\Commands;

use Illuminate\Validation\ValidationException;
use Modules\Products\Domain\Ports\ContentGenerationDispatcherPort;
use Modules\Products\Domain\Ports\ContentGenerationRepositoryPort;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ContentGenerationEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Shared\Domain\Ports\AuditPort;

/**
 * Queues (or re-queues) the ZIP deliverable for the product's latest completed
 * generation (spec US-5).
 */
final readonly class RequestProductPackageHandler
{
    public function __construct(
        private ContentGenerationRepositoryPort $generations,
        private ContentGenerationDispatcherPort $dispatcher,
        private AuditPort $audit,
    ) {}

    #[\NoDiscard]
    public function handle(ProductEloquentModel $product, ?object $causer = null): ContentGenerationEloquentModel
    {
        $generation = $this->generations->latestCompletedFor($product->id)
            ?? throw ValidationException::withMessages([
                'product' => [__('This product has no completed content generation to package.')],
            ]);

        if ($this->generations->hasInFlightFor($product->id)) {
            throw ValidationException::withMessages([
                'product' => [__('Wait for the running generation to finish before packaging.')],
            ]);
        }

        $this->dispatcher->dispatchPackaging(
            $generation->uuid,
            $causer !== null ? (int) $causer->getAuthIdentifier() : null,
        );

        $this->audit->log(
            event: 'products.package.requested',
            subject: $generation,
            properties: [
                'product_uuid' => $product->uuid,
                'generation_uuid' => $generation->uuid,
            ],
            causer: $causer,
            logName: 'products',
        );

        return $generation->refresh();
    }
}

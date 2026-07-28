<?php

declare(strict_types=1);

namespace Modules\Products\Application\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Products\Application\DTOs\GenerateContentData;
use Modules\Products\Application\Queries\GetContentGenerationStatusHandler;
use Modules\Products\Domain\Enums\GenerationStatus;
use Modules\Products\Domain\Ports\ContentGenerationDispatcherPort;
use Modules\Products\Domain\Ports\ContentGenerationRepositoryPort;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ContentGenerationEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Shared\Domain\Ports\AuditPort;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Entry point of the async pipeline (spec US-2). Returns as soon as the
 * `pending` row exists — the operator then polls
 * {@see GetContentGenerationStatusHandler}
 * instead of holding an HTTP request open for minutes of AI work.
 *
 * Three gates before anything is persisted:
 *  - the product type must be generatable (422, spec FR-1);
 *  - the seed markdown must fit the configured byte cap (422, OWASP
 *    unrestricted resource consumption / LLM cost abuse);
 *  - no other run may be in flight for this product (409, spec FR-14).
 */
final readonly class StartContentGenerationHandler
{
    private const int DEFAULT_MAX_MARKDOWN_BYTES = 1_048_576;

    public function __construct(
        private ContentGenerationRepositoryPort $generations,
        private ContentGenerationDispatcherPort $dispatcher,
        private AuditPort $audit,
    ) {}

    #[\NoDiscard]
    public function handle(
        ProductEloquentModel $product,
        GenerateContentData $data,
        object $causer,
    ): ContentGenerationEloquentModel {
        $this->guardGeneratableType($product);
        $this->guardMarkdownSize($data);
        $this->guardNoRunInFlight($product);

        $generation = DB::transaction(fn (): ContentGenerationEloquentModel => $this->generations->create([
            'product_id' => $product->id,
            'user_id' => $causer->id,
            'status' => GenerationStatus::Pending->value,
            'mode' => $data->mode,
            'source_markdown' => $data->markdown,
            'progress' => 0,
        ]));

        $this->dispatcher->dispatch(
            $generation->uuid,
            (int) $causer->getAuthIdentifier(),
        );

        // Never audit the seed itself: it can be up to 1 MB of client material.
        // Length + type are enough to trace abuse without storing the payload.
        $this->audit->log(
            event: 'products.generation.started',
            subject: $generation,
            properties: [
                'product_uuid' => $product->uuid,
                'product_type' => $product->type instanceof \BackedEnum ? $product->type->value : $product->type,
                'mode' => $data->mode,
                'markdown_length' => strlen($data->markdown),
            ],
            causer: $causer,
            logName: 'products',
        );

        return $generation;
    }

    private function guardGeneratableType(ProductEloquentModel $product): void
    {
        if (! $product->productType()->isGeneratable()) {
            throw ValidationException::withMessages([
                'type' => [__('This product type does not support content generation.')],
            ]);
        }
    }

    private function guardMarkdownSize(GenerateContentData $data): void
    {
        $max = (int) config('products.generation.max_markdown_bytes', self::DEFAULT_MAX_MARKDOWN_BYTES);

        if (trim($data->markdown) === '') {
            throw ValidationException::withMessages([
                'markdown' => [__('The seed markdown is empty.')],
            ]);
        }

        if (strlen($data->markdown) > $max) {
            throw ValidationException::withMessages([
                'markdown' => [__('The seed markdown exceeds the :max byte limit.', ['max' => $max])],
            ]);
        }
    }

    private function guardNoRunInFlight(ProductEloquentModel $product): void
    {
        if ($this->generations->hasInFlightFor($product->id)) {
            throw new ConflictHttpException(
                __('A content generation is already running for this product.'),
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Ports;

use Modules\Products\Application\DTOs\ContentTreeCountsData;
use Modules\Products\Application\DTOs\CourseDocumentData;
use Modules\Products\Application\DTOs\GenerationTopicData;
use Modules\Products\Application\DTOs\SeedOutlineData;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ContentGenerationEloquentModel;

/**
 * Persistence for a generation run and the session/topic tree it owns.
 *
 * CRUD methods serve StartContentGeneration / package download; tree methods
 * serve the async pipeline stages (parsing → generating → rendering).
 */
interface ContentGenerationRepositoryPort
{
    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): ContentGenerationEloquentModel;

    public function findByUuid(string $uuid): ?ContentGenerationEloquentModel;

    public function findByUuidForProduct(string $uuid, int $productId): ?ContentGenerationEloquentModel;

    public function findInFlightForProduct(int $productId): ?ContentGenerationEloquentModel;

    public function hasInFlightFor(int $productId): bool;

    public function latestCompletedFor(int $productId): ?ContentGenerationEloquentModel;

    /** Most recent generation of any status (for Show-page status chip). */
    public function latestForProduct(int $productId): ?ContentGenerationEloquentModel;

    /** @param  array<string, mixed>  $attributes */
    public function update(
        ContentGenerationEloquentModel $generation,
        array $attributes,
    ): ContentGenerationEloquentModel;

    /**
     * Replace mode: soft-delete the product's current sessions and rebuild the
     * tree from the seed. When `$forceReplace` is false, verified/recorded
     * scripts whose topic title still appears in the seed are restored onto the
     * new tree (clarify Q7). Force mode overwrites everything.
     */
    public function replaceContentTree(
        string $productUuid,
        SeedOutlineData $outline,
        bool $forceReplace = false,
    ): ContentTreeCountsData;

    /**
     * @return list<GenerationTopicData>
     */
    public function topicsForProduct(string $productUuid): array;

    public function courseDocument(string $productUuid): CourseDocumentData;
}

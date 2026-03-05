<?php

declare(strict_types=1);

namespace Modules\Blog\Domain\Ports;

use Modules\Blog\Domain\Entities\BlogCategory;

/**
 * BlogCategoryRepositoryPort — Domain interface for BlogCategory persistence.
 *
 * Infrastructure implements this via EloquentBlogCategoryRepository.
 * All public-facing lookups use UUID, never internal integer ID.
 */
interface BlogCategoryRepositoryPort
{
    public function findByUuid(string $uuid): ?BlogCategory;

    /**
     * @param array<string, mixed> $filters
     * @return array{data: list<BlogCategory>, total: int, perPage: int, currentPage: int, lastPage: int}
     */
    public function findAllPaginated(array $filters = [], int $page = 1, int $perPage = 15): array;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): BlogCategory;

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $uuid, array $data): BlogCategory;

    public function softDelete(string $uuid): void;

    public function restore(string $uuid): void;
}

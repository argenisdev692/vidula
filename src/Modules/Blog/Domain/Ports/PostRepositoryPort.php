<?php

declare(strict_types=1);

namespace Modules\Blog\Domain\Ports;

use Modules\Blog\Domain\Entities\Post;

interface PostRepositoryPort
{
    public function findByUuid(string $uuid): ?Post;

    public function findCategoryIdByUuid(string $uuid): ?int;

    public function findAllPaginated(array $filters = [], int $page = 1, int $perPage = 15): array;

    public function create(array $data): Post;

    public function update(string $uuid, array $data): Post;

    public function softDelete(string $uuid): void;

    public function restore(string $uuid): void;
}

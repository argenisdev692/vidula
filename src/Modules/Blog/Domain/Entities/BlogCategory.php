<?php

declare(strict_types=1);

namespace Modules\Blog\Domain\Entities;

use Modules\Blog\Domain\ValueObjects\BlogCategoryId;
use Shared\Domain\Entities\AggregateRoot;

/**
 * BlogCategory — Domain Entity (Aggregate Root)
 *
 * Represents a blog category in the Blog bounded context.
 * Agnostic of Eloquent / infrastructure.
 */
final class BlogCategory extends AggregateRoot
{
    public function __construct(
        public BlogCategoryId $id,
        public string $uuid,
        public string $name,
        public ?string $description = null,
        public ?string $image = null,
        public ?int $userId = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $deletedAt = null,
    ) {
    }

    /**
     * Soft delete the blog category
     */
    public function softDelete(): self
    {
        return clone($this, [
            'updatedAt' => date('Y-m-d H:i:s'),
            'deletedAt' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Restore a soft-deleted blog category
     */
    public function restore(): self
    {
        return clone($this, [
            'updatedAt' => date('Y-m-d H:i:s'),
            'deletedAt' => null,
        ]);
    }
}

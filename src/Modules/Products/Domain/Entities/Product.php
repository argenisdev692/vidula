<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Entities;

use Modules\Products\Domain\Enums\ProductLevel;
use Modules\Products\Domain\Enums\ProductStatus;
use Modules\Products\Domain\Enums\ProductType;
use Modules\Products\Domain\Events\ProductCreated;
use Modules\Products\Domain\Events\ProductUpdated;
use Modules\Products\Domain\ValueObjects\Money;
use Modules\Products\Domain\ValueObjects\ProductId;
use Modules\Products\Domain\ValueObjects\UserId;
use Shared\Domain\Entities\AggregateRoot;

final class Product extends AggregateRoot
{
    private function __construct(
        public readonly ProductId $id,
        public readonly UserId $userId,
        public readonly ProductType $type,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly Money $price,
        public readonly ProductStatus $status,
        public readonly ?string $thumbnail,
        public readonly ProductLevel $level,
        public readonly string $language,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?string $deletedAt = null
    ) {}

    public static function create(
        ProductId $id,
        UserId $userId,
        ProductType $type,
        string $title,
        string $slug,
        ?string $description,
        Money $price,
        ProductLevel $level,
        string $language,
        ?string $thumbnail = null
    ): self {
        $product = new self(
            id: $id,
            userId: $userId,
            type: $type,
            title: $title,
            slug: $slug,
            description: $description,
            price: $price,
            status: ProductStatus::Draft,
            thumbnail: $thumbnail,
            level: $level,
            language: $language,
            createdAt: now()->toIso8601String()
        );

        $product->recordEvent(new ProductCreated(
            aggregateId: $id->value,
            title: $title,
            occurredOn: now()->toDateTimeString()
        ));

        return $product;
    }

    public function update(
        string $title,
        string $slug,
        ?string $description,
        Money $price,
        ProductLevel $level,
        string $language,
        ?string $thumbnail
    ): self {
        $updated = clone($this, [
            'title' => $title,
            'slug' => $slug,
            'description' => $description,
            'price' => $price,
            'level' => $level,
            'language' => $language,
            'thumbnail' => $thumbnail,
            'updatedAt' => now()->toIso8601String()
        ]);

        $updated->recordEvent(new ProductUpdated(
            aggregateId: $this->id->value,
            title: $title,
            occurredOn: now()->toDateTimeString()
        ));

        return $updated;
    }

    public function publish(): self
    {
        if ($this->status->isPublished()) {
            return $this;
        }

        return clone($this, [
            'status' => ProductStatus::Published,
            'updatedAt' => now()->toIso8601String()
        ]);
    }

    public function archive(): self
    {
        if ($this->status->isArchived()) {
            return $this;
        }

        return clone($this, [
            'status' => ProductStatus::Archived,
            'updatedAt' => now()->toIso8601String()
        ]);
    }

    public function updateThumbnail(?string $thumbnail): self
    {
        return clone($this, [
            'thumbnail' => $thumbnail,
            'updatedAt' => now()->toIso8601String()
        ]);
    }

    public function changePrice(Money $newPrice): self
    {
        return clone($this, [
            'price' => $newPrice,
            'updatedAt' => now()->toIso8601String()
        ]);
    }

    public function isPublished(): bool
    {
        return $this->status->isPublished();
    }

    public function isDraft(): bool
    {
        return $this->status->isDraft();
    }

    public function isArchived(): bool
    {
        return $this->status->isArchived();
    }

    public function isFree(): bool
    {
        return $this->price->isZero();
    }
}

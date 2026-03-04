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
    public function __construct(
        public ProductId $id,
        public UserId $userId,
        public ProductType $type,
        public string $title,
        public string $slug,
        public ?string $description,
        public Money $price,
        public ProductStatus $status,
        public ?string $thumbnail,
        public ProductLevel $level,
        public string $language,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $deletedAt = null
    ) {
    }

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
            createdAt: date('c')
        );

        $product->recordDomainEvent(new ProductCreated(
            aggregateId: $id->value,
            title: $title,
            occurredOn: date('c')
        ));

        return $product;
    }

    #[\NoDiscard('Callers must capture the updated Product entity')]
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
            'updatedAt' => date('c'),
        ]);

        $updated->recordDomainEvent(new ProductUpdated(
            aggregateId: $this->id->value,
            title: $title,
            occurredOn: date('c')
        ));

        return $updated;
    }

    #[\NoDiscard('Callers must capture the published Product entity')]
    public function publish(): self
    {
        if ($this->status->isPublished()) {
            return $this;
        }

        return clone($this, [
            'status' => ProductStatus::Published,
            'updatedAt' => date('c'),
        ]);
    }

    #[\NoDiscard('Callers must capture the archived Product entity')]
    public function archive(): self
    {
        if ($this->status->isArchived()) {
            return $this;
        }

        return clone($this, [
            'status' => ProductStatus::Archived,
            'updatedAt' => date('c'),
        ]);
    }

    #[\NoDiscard('Callers must capture the Product entity with updated thumbnail')]
    public function updateThumbnail(?string $thumbnail): self
    {
        return clone($this, [
            'thumbnail' => $thumbnail,
            'updatedAt' => date('c'),
        ]);
    }

    #[\NoDiscard('Callers must capture the Product entity with changed price')]
    public function changePrice(Money $newPrice): self
    {
        return clone($this, [
            'price' => $newPrice,
            'updatedAt' => date('c'),
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

<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    #[\NoDiscard]
    public function label(): string
    {
        return match($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    #[\NoDiscard]
    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    #[\NoDiscard]
    public function isPublished(): bool
    {
        return $this === self::Published;
    }

    #[\NoDiscard]
    public function isArchived(): bool
    {
        return $this === self::Archived;
    }
}

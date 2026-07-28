<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Enums;

/**
 * Catalog lifecycle of a product. Independent from the soft-delete state,
 * which the list filters expose as active|suspended.
 */
enum ProductStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}

<?php

declare(strict_types=1);

namespace Modules\Blog\Application\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * UpdateBlogCategoryDTO — Data Transfer Object for blog category updates.
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class UpdateBlogCategoryDTO extends Data
{
    public function __construct(
        public ?string $blogCategoryName = null,
        public ?string $blogCategoryDescription = null,
        public ?string $blogCategoryImage = null,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Modules\Blog\Application\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * CreateBlogCategoryDTO — Data Transfer Object for blog category creation.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class CreateBlogCategoryDTO extends Data
{
    public function __construct(
        public string $blogCategoryName,
        public ?string $blogCategoryDescription = null,
        public ?string $blogCategoryImage = null,
    ) {
    }
}

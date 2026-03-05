<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Blog\Application\Queries\ReadModels\BlogCategoryReadModel;
use Modules\Blog\Domain\Entities\BlogCategory;

/**
 * BlogCategoryResource — API output representation.
 */
final class BlogCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isDomain = $this->resource instanceof BlogCategory;
        $isReadModel = $this->resource instanceof BlogCategoryReadModel;

        return [
            'uuid' => $this->resource->uuid,
            'blog_category_name' => ($isDomain) ? $this->resource->name : $this->resource->blogCategoryName,
            'blog_category_description' => ($isDomain) ? $this->resource->description : $this->resource->blogCategoryDescription,
            'blog_category_image' => ($isDomain) ? $this->resource->image : $this->resource->blogCategoryImage,
            'user_id' => ($isDomain) ? $this->resource->userId : ($isReadModel ? $this->resource->userId : null),
            'created_at' => ($isDomain || $isReadModel) ? $this->resource->createdAt : $this->resource->created_at,
            'updated_at' => ($isDomain || $isReadModel) ? $this->resource->updatedAt : $this->resource->updated_at,
        ];
    }
}

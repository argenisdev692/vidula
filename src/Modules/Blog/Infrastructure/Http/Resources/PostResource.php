<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Blog\Application\Queries\ReadModels\PostReadModel;
use Modules\Blog\Domain\Entities\Post;

final class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isDomain = $this->resource instanceof Post;
        $isReadModel = $this->resource instanceof PostReadModel;

        return [
            'uuid' => $this->resource->uuid,
            'post_title' => $isDomain ? $this->resource->title : $this->resource->postTitle,
            'post_title_slug' => $isDomain ? $this->resource->slug : $this->resource->postTitleSlug,
            'post_content' => $isDomain ? $this->resource->content : $this->resource->postContent,
            'post_excerpt' => $isDomain ? $this->resource->excerpt : $this->resource->postExcerpt,
            'post_cover_image' => $isDomain ? $this->resource->coverImage : $this->resource->postCoverImage,
            'meta_title' => $isDomain ? $this->resource->metaTitle : $this->resource->metaTitle,
            'meta_description' => $isDomain ? $this->resource->metaDescription : $this->resource->metaDescription,
            'meta_keywords' => $isDomain ? $this->resource->metaKeywords : $this->resource->metaKeywords,
            'category_uuid' => $isDomain ? $this->resource->categoryUuid : $this->resource->categoryUuid,
            'category_name' => $isDomain ? $this->resource->categoryName : $this->resource->categoryName,
            'user_id' => $isDomain ? $this->resource->userId : $this->resource->userId,
            'post_status' => $isDomain ? $this->resource->status : $this->resource->postStatus,
            'published_at' => $isDomain ? $this->resource->publishedAt : $this->resource->publishedAt,
            'scheduled_at' => $isDomain ? $this->resource->scheduledAt : $this->resource->scheduledAt,
            'created_at' => $isDomain ? $this->resource->createdAt : $this->resource->createdAt,
            'updated_at' => $isDomain ? $this->resource->updatedAt : $this->resource->updatedAt,
            'deleted_at' => $isDomain ? $this->resource->deletedAt : $this->resource->deletedAt,
        ];
    }
}

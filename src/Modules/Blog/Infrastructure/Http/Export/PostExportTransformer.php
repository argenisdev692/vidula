<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Export;

use Modules\Blog\Infrastructure\Persistence\Eloquent\Models\PostEloquentModel;

final class PostExportTransformer
{
    public static function transform(PostEloquentModel $post): array
    {
        return [
            $post->uuid,
            $post->post_title,
            $post->post_title_slug,
            $post->category?->blog_category_name ?? 'Uncategorized',
            $post->post_status,
            $post->published_at?->format('Y-m-d H:i:s') ?? '—',
            $post->created_at?->format('Y-m-d H:i:s') ?? '—',
        ];
    }

    public static function transformForPdf(PostEloquentModel $post): array
    {
        return [
            'uuid' => $post->uuid,
            'title' => $post->post_title,
            'slug' => $post->post_title_slug,
            'category' => $post->category?->blog_category_name ?? 'Uncategorized',
            'status' => $post->post_status,
            'published_at' => $post->published_at?->format('Y-m-d H:i') ?? '—',
            'created_at' => $post->created_at?->format('Y-m-d H:i') ?? '—',
        ];
    }
}

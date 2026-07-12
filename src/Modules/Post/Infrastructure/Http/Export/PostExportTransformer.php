<?php

declare(strict_types=1);

namespace Modules\Post\Infrastructure\Http\Export;

use Modules\Post\Infrastructure\Persistence\Eloquent\Models\PostEloquentModel;
use Shared\Domain\Ports\ExportPort;

/**
 * Maps a {@see PostEloquentModel} row to export columns so CSV, Excel and PDF
 * stay consistent with the on-screen list. The module ships only this
 * transformer — the writer / streamer / PDF renderer live behind the Shared
 * {@see ExportPort} (BACKEND-PHP §8). The cover image and
 * full body are intentionally omitted (a tabular/PDF report carries text
 * metadata, not binaries or long-form content).
 */
final readonly class PostExportTransformer
{
    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForTable(PostEloquentModel $post): array
    {
        return [
            'Title' => $post->post_title,
            'Category' => $post->category?->blog_category_name ?? '—',
            'Author' => self::authorLabel($post),
            'Status' => ucfirst($post->post_status->value),
            'SEO Score' => $post->seo_score !== null ? (string) $post->seo_score : '—',
            'Created' => $post->created_at?->toDateTimeString() ?? '—',
            'Suspended' => $post->deleted_at !== null ? 'Yes' : 'No',
        ];
    }

    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForPdf(PostEloquentModel $post): array
    {
        return self::transformForTable($post);
    }

    private static function authorLabel(PostEloquentModel $post): string
    {
        $name = trim(sprintf('%s %s', $post->user?->first_name ?? '', $post->user?->last_name ?? ''));

        return $name !== '' ? $name : 'System';
    }
}

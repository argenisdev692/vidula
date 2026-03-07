<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\CLI;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Blog\Infrastructure\Persistence\Eloquent\Models\PostEloquentModel;
use Shared\Infrastructure\Audit\AuditInterface;

final class PublishScheduledPostsCommand extends Command
{
    protected $signature = 'blog:publish-scheduled-posts';

    protected $description = 'Publish scheduled blog posts that are due';

    public function __construct(
        private readonly AuditInterface $audit,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $now = now();
        $publishedCount = 0;
        $publishedUuids = [];

        PostEloquentModel::query()
            ->select(['id', 'uuid'])
            ->where('post_status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->orderBy('id')
            ->chunkById(100, function (Collection $posts) use ($now, &$publishedCount, &$publishedUuids): void {
                $ids = $posts->pluck('id')->all();
                $uuids = $posts->pluck('uuid')->all();

                $affectedRows = PostEloquentModel::query()
                    ->whereIn('id', $ids)
                    ->update([
                        'post_status' => 'published',
                        'published_at' => $now,
                        'scheduled_at' => null,
                        'updated_at' => $now,
                    ]);

                $publishedCount += $affectedRows;
                $publishedUuids = [...$publishedUuids, ...$uuids];
            });

        foreach ($publishedUuids as $uuid) {
            Cache::forget("post_read_{$uuid}");
        }

        try {
            Cache::tags(['posts_list'])->flush();
        } catch (\Exception) {
        }

        if ($publishedCount > 0) {
            $this->audit->log(
                logName: 'posts.scheduled_published',
                description: 'Scheduled blog posts published',
                properties: [
                    'count' => $publishedCount,
                    'uuids' => $publishedUuids,
                ],
            );
        }

        $this->info("Published {$publishedCount} scheduled post(s).");

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Post\Application\Commands;

use Modules\Post\Application\DTOs\PostTopicIdeaData;
use Modules\Post\Application\DTOs\SuggestPostTopicsData;
use Modules\Post\Domain\Ports\PostTopicIdeatorPort;

/**
 * Imperative AI action (no DB write) — same shape as Backup's
 * `RunBackupHandler`: it lives under Commands because it is a costly,
 * non-idempotent external call, even though it does not mutate a Post
 * aggregate. Authorization (permission:CREATE_POSTS) is enforced at the route.
 */
final readonly class SuggestPostTopicsHandler
{
    public function __construct(private PostTopicIdeatorPort $ideator) {}

    /**
     * @return list<PostTopicIdeaData>
     */
    public function handle(SuggestPostTopicsData $data): array
    {
        return $this->ideator->suggestTopics($data);
    }
}

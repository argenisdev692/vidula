<?php

declare(strict_types=1);

namespace Modules\Post\Application\Commands;

use Modules\Post\Application\DTOs\GeneratedPostContentData;
use Modules\Post\Application\DTOs\GeneratePostContentData;
use Modules\Post\Domain\Ports\PostContentGeneratorPort;
use Shared\Domain\Ports\AuditPort;

/**
 * Imperative AI action (no DB write) — mirrors {@see SuggestPostTopicsHandler},
 * including the meta-audit of who triggered the (billed) generation.
 * Authorization (permission:CREATE_POSTS) is enforced at the route.
 */
final readonly class GeneratePostContentHandler
{
    public function __construct(
        private PostContentGeneratorPort $generator,
        private AuditPort $audit,
    ) {}

    public function handle(GeneratePostContentData $data, ?object $causer = null): GeneratedPostContentData
    {
        $draft = $this->generator->generate($data, $causer);

        $this->audit->log(
            event: 'post.ai.content_generated',
            properties: [
                'provider' => $data->provider,
                'topic' => $data->topic,
                'generate_cover_image' => $data->generateCoverImage,
                'seo_score' => $draft->seoScore,
                'human_writing_index' => $draft->humanWritingIndex,
            ],
            causer: $causer,
            logName: 'post',
        );

        return $draft;
    }
}

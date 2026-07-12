<?php

declare(strict_types=1);

namespace Modules\Post\Application\Commands;

use Modules\Post\Application\DTOs\GeneratedPostContentData;
use Modules\Post\Application\DTOs\GeneratePostContentData;
use Modules\Post\Domain\Ports\PostContentGeneratorPort;

/**
 * Imperative AI action (no DB write) — mirrors {@see SuggestPostTopicsHandler}.
 * Authorization (permission:CREATE_POSTS) is enforced at the route.
 */
final readonly class GeneratePostContentHandler
{
    public function __construct(private PostContentGeneratorPort $generator) {}

    public function handle(GeneratePostContentData $data): GeneratedPostContentData
    {
        return $this->generator->generate($data);
    }
}

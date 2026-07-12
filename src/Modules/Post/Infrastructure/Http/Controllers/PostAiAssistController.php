<?php

declare(strict_types=1);

namespace Modules\Post\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Post\Application\Commands\GeneratePostContentHandler;
use Modules\Post\Application\Commands\SuggestPostTopicsHandler;
use Modules\Post\Application\DTOs\GeneratePostContentData;
use Modules\Post\Application\DTOs\SuggestPostTopicsData;

/**
 * XHR-only AI actions consumed by the Create/Edit AI-assist panel. Both are
 * read-only with respect to the Post aggregate — nothing is persisted until
 * the user reviews the draft and submits the normal store/update route.
 * Gated by `permission:CREATE_POSTS` + a tight `throttle:` (real API cost per
 * call) — see Routes.
 */
final readonly class PostAiAssistController
{
    public function __construct(
        private SuggestPostTopicsHandler $suggestTopics,
        private GeneratePostContentHandler $generateContent,
    ) {}

    public function suggestTopics(SuggestPostTopicsData $data): JsonResponse
    {
        return response()->json(['data' => $this->suggestTopics->handle($data)]);
    }

    public function generateContent(GeneratePostContentData $data): JsonResponse
    {
        return response()->json(['data' => $this->generateContent->handle($data)]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Post\Domain\Ports;

use Modules\Post\Application\DTOs\GeneratedPostContentData;
use Modules\Post\Application\DTOs\GeneratePostContentData;

/**
 * Generates a complete, SEO/EEAT-scored blog draft (and optionally a cover
 * image) for a chosen topic. Read-only — the caller decides whether/when to
 * persist the result via CreatePostHandler / UpdatePostHandler.
 */
interface PostContentGeneratorPort
{
    public function generate(GeneratePostContentData $data): GeneratedPostContentData;
}

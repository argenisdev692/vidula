<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Tests\Unit;

use Modules\AiResumeStudio\Infrastructure\Export\RefinedCvMarkdownRenderer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RefinedCvMarkdownRendererTest extends TestCase
{
    #[Test]
    public function it_renders_headings_and_lists_without_scripts(): void
    {
        $html = (new RefinedCvMarkdownRenderer)->toHtml(
            "# Jane Doe\n\n## Experience\n\n- Built APIs\n\n<script>alert(1)</script>",
        );

        $this->assertStringContainsString('<h1>', $html);
        $this->assertStringContainsString('<h2>', $html);
        $this->assertStringContainsString('<li>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
    }
}

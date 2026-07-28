<?php

declare(strict_types=1);

namespace Modules\Products\Tests\Unit;

use Modules\Products\Application\Services\SeedOutlineParser;
use Modules\Products\Domain\Enums\ProductType;
use Modules\Products\Domain\Exceptions\SeedOutlineException;
use Tests\TestCase;

final class SeedOutlineParserTest extends TestCase
{
    private SeedOutlineParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new SeedOutlineParser;
    }

    public function test_parses_classroom_fixture_from_module_products(): void
    {
        $markdown = (string) file_get_contents(base_path('tests/Fixtures/products/indice-curso-copilot.md'));

        $outline = $this->parser->parse($markdown, ProductType::Classroom);

        $this->assertGreaterThanOrEqual(1, count($outline->sessions));
        $this->assertGreaterThanOrEqual(1, $outline->topicCount());
        $this->assertSame(1, $outline->sessions[0]->sessionNumber);
        $this->assertNotSame('', $outline->sessions[0]->topics[0]->title);
    }

    public function test_parses_video_pills_fixture(): void
    {
        $markdown = (string) file_get_contents(base_path('tests/Fixtures/products/pildoras_video_claude_usuarios.md'));

        $outline = $this->parser->parse($markdown, ProductType::VideoPill);

        $this->assertGreaterThanOrEqual(1, count($outline->sessions));
        $this->assertGreaterThanOrEqual(1, $outline->topicCount());
    }

    public function test_empty_markdown_throws(): void
    {
        $this->expectException(SeedOutlineException::class);
        $this->parser->parse('   ', ProductType::Classroom);
    }

    public function test_unparseable_markdown_throws(): void
    {
        $this->expectException(SeedOutlineException::class);
        $this->parser->parse("# Just a title\n\nNo sessions here.", ProductType::Classroom);
    }

    public function test_minimal_classroom_shape(): void
    {
        $md = <<<'MD'
### Sesión 1 | Intro
- **Tema 1:** First topic
- **Tema 2:** Second topic
MD;

        $outline = $this->parser->parse($md, ProductType::Classroom);

        $this->assertCount(1, $outline->sessions);
        $this->assertCount(2, $outline->sessions[0]->topics);
        $this->assertSame('First topic', $outline->sessions[0]->topics[0]->title);
    }
}

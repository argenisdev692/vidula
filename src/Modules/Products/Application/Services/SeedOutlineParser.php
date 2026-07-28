<?php

declare(strict_types=1);

namespace Modules\Products\Application\Services;

use Modules\Products\Application\DTOs\SeedOutlineData;
use Modules\Products\Application\DTOs\SeedSessionData;
use Modules\Products\Application\DTOs\SeedTopicData;
use Modules\Products\Domain\Enums\ProductType;
use Modules\Products\Domain\Exceptions\SeedOutlineException;

/**
 * Reads an operator's course/video index markdown into sessions → topics.
 *
 * Two real-world shapes are supported, taken verbatim from the indexes the
 * business already ships (`docs/MODULE-PRODUCTS`):
 *
 * - **Classroom** — `### Sesión N | 11 Nov - Title` headings whose body lists
 *   `- **Tema k:** Title` bullets (sub-bullets are outline detail, ignored).
 * - **Video** — `### BLOQUE N – Title (52 min)` headings followed by a
 *   markdown table whose second column is the video title; when a block has
 *   no table, or the document has no blocks at all, `## VÍDEO N – Title`
 *   detail headings are used instead.
 *
 * Application service (produces Spatie Data DTOs). Caps guard both DB volume
 * and downstream LLM spend (OWASP LLM10 unbounded consumption). Domain rules
 * surface via {@see SeedOutlineException}.
 */
final readonly class SeedOutlineParser
{
    private const int DEFAULT_MAX_SESSIONS = 200;

    private const int DEFAULT_MAX_TOPICS = 2000;

    #[\NoDiscard]
    public function parse(string $markdown, ProductType $type): SeedOutlineData
    {
        $normalized = trim(str_replace(["\r\n", "\r"], "\n", $markdown));

        if ($normalized === '') {
            throw SeedOutlineException::empty();
        }

        $sessions = $type->isVideo()
            ? $this->parseVideoOutline($normalized)
            : $this->parseClassroomOutline($normalized);

        if ($sessions === []) {
            throw SeedOutlineException::unparseable();
        }

        $outline = new SeedOutlineData($sessions);
        $maxSessions = (int) config('products.generation.max_sessions', self::DEFAULT_MAX_SESSIONS);
        $maxTopics = (int) config('products.generation.max_topics', self::DEFAULT_MAX_TOPICS);

        if (count($sessions) > $maxSessions) {
            throw SeedOutlineException::tooManySessions($maxSessions);
        }

        if ($outline->topicCount() > $maxTopics) {
            throw SeedOutlineException::tooManyTopics($maxTopics);
        }

        if ($outline->topicCount() === 0) {
            throw SeedOutlineException::unparseable();
        }

        return $outline;
    }

    /**
     * @return list<SeedSessionData>
     */
    private function parseClassroomOutline(string $markdown): array
    {
        $sessions = [];

        foreach ($this->splitByHeading($markdown, '/^###\s+Sesi[oó]n\s+(\d+)\s*[|–—-]\s*(.+)$/miu') as $block) {
            $sessions[] = new SeedSessionData(
                sessionNumber: (int) $block['number'],
                title: $block['title'],
                topics: $this->extractBulletTopics($block['body']),
            );
        }

        return $sessions;
    }

    /**
     * @return list<SeedSessionData>
     */
    private function parseVideoOutline(string $markdown): array
    {
        $blocks = $this->splitByHeading($markdown, '/^###\s+BLOQUE\s+(\d+)\s*[–—-]\s*(.+?)(?:\s*\(\d+\s*min\))?\s*$/miu');

        if ($blocks !== []) {
            return array_map(
                function (array $block): SeedSessionData {
                    $topics = $this->extractTableTopics($block['body']);

                    return new SeedSessionData(
                        sessionNumber: (int) $block['number'],
                        title: $block['title'],
                        topics: $topics !== [] ? $topics : $this->extractVideoHeadingTopics($block['body']),
                    );
                },
                $blocks,
            );
        }

        $videos = $this->extractVideoHeadingTopics($markdown);

        return $videos === [] ? [] : [new SeedSessionData(1, 'Videos', $videos)];
    }

    /**
     * Splits the document on a heading regex capturing (number, title) and
     * returns each heading with the body that follows it up to the next one.
     *
     * @return list<array{number: string, title: string, body: string}>
     */
    private function splitByHeading(string $markdown, string $pattern): array
    {
        if (preg_match_all($pattern, $markdown, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) === 0) {
            return [];
        }

        $blocks = [];
        $total = count($matches);

        foreach ($matches as $index => $match) {
            $start = $match[0][1] + strlen($match[0][0]);
            $end = $index + 1 < $total ? $matches[$index + 1][0][1] : strlen($markdown);

            $blocks[] = [
                'number' => $match[1][0],
                'title' => trim($match[2][0]),
                'body' => substr($markdown, $start, $end - $start),
            ];
        }

        return $blocks;
    }

    /**
     * @return list<SeedTopicData>
     */
    private function extractBulletTopics(string $body): array
    {
        if (preg_match_all('/^-\s+\*\*Tema\s+\d+:\*\*\s*(.+)$/miu', $body, $matches) === 0) {
            return [];
        }

        $topics = [];

        foreach ($matches[1] as $title) {
            $clean = trim($title);

            if ($clean !== '') {
                $topics[] = new SeedTopicData($clean, count($topics) + 1);
            }
        }

        return $topics;
    }

    /**
     * Reads video titles out of a `| Nº | Vídeo | Tema | Duración |` table,
     * skipping the header and separator rows.
     *
     * @return list<SeedTopicData>
     */
    private function extractTableTopics(string $body): array
    {
        $topics = [];

        foreach (explode("\n", $body) as $line) {
            $trimmed = trim($line);

            if (! str_starts_with($trimmed, '|')) {
                continue;
            }

            if (preg_match('/^\|\s*-+/u', $trimmed) === 1 || preg_match('/N[ºo]|V[ií]deo|Tema/iu', $trimmed) === 1) {
                continue;
            }

            $cells = array_values(array_filter(
                array_map(trim(...), explode('|', $trimmed)),
                static fn (string $cell): bool => $cell !== '',
            ));

            if (count($cells) < 2) {
                continue;
            }

            $title = count($cells) >= 3 && ctype_digit($cells[0]) ? $cells[1] : $cells[0];

            if ($title === '' || ctype_digit($title)) {
                continue;
            }

            $topics[] = new SeedTopicData($title, count($topics) + 1);
        }

        return $topics;
    }

    /**
     * @return list<SeedTopicData>
     */
    private function extractVideoHeadingTopics(string $markdown): array
    {
        if (preg_match_all('/^##\s+V[IÍ]DEO\s+(\d+)\s*[–—-]\s*(.+)$/miu', $markdown, $matches) === 0) {
            return [];
        }

        $topics = [];

        foreach ($matches[2] as $title) {
            $clean = trim($title);

            if ($clean !== '') {
                $topics[] = new SeedTopicData($clean, count($topics) + 1);
            }
        }

        return $topics;
    }
}

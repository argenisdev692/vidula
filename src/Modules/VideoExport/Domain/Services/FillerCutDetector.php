<?php

declare(strict_types=1);

namespace Modules\VideoExport\Domain\Services;

use Modules\VideoExport\Domain\ValueObjects\TimeRange;
use Modules\VideoExport\Domain\ValueObjects\WordTimestamp;

/**
 * Transcript-driven cut detection (fillers, stutters, PAUSA). Ported from Nest FillerService.
 */
final readonly class FillerCutDetector
{
    /** @var list<string> */
    private const array FILLER_REGEX = [
        '/^e+h+$/',
        '/^e+m+$/',
        '/^h?m{2,}$/',
        '/^u+h+$/',
        '/^u+m+$/',
    ];

    /** @var list<string> */
    private const array PAUSE_MARKER_WORDS = [
        'pausa', 'pauza', 'pauso', 'pausas', 'pausar', 'pause', 'pousa',
    ];

    /**
     * @param  list<string>  $fillerTerms
     * @param  list<string>  $pauseKeywords
     * @param  array{silence_threshold_seconds: float, max_seconds: float}  $pauseBacktrack
     * @param  array{max_gap_seconds: float, max_token_chars: int}  $stutter
     */
    public function __construct(
        private array $fillerTerms,
        private array $pauseKeywords,
        private array $pauseBacktrack,
        private array $stutter,
        private float $minSegmentSeconds = 0.25,
    ) {}

    /**
     * @param  list<WordTimestamp>  $words
     * @return list<TimeRange>
     */
    public function findFillerCuts(array $words): array
    {
        $fillerSet = [];
        foreach ($this->fillerTerms as $term) {
            $token = self::cleanToken($term);
            if ($token !== '') {
                $fillerSet[$token] = true;
            }
        }

        $cuts = [];
        foreach ($words as $word) {
            $token = self::cleanToken($word->text);
            if (! $this->isFiller($token, $fillerSet)) {
                continue;
            }
            $cuts[] = $this->expandToMin(max(0.0, $word->start - 0.05), $word->end + 0.05);
        }

        return $cuts;
    }

    /**
     * @param  list<WordTimestamp>  $words
     * @return list<TimeRange>
     */
    public function findStutterCuts(array $words): array
    {
        $tokens = array_map(static fn (WordTimestamp $w): string => self::cleanToken($w->text), $words);
        $cuts = [];
        $index = 0;
        $maxGap = (float) $this->stutter['max_gap_seconds'];
        $maxChars = (int) $this->stutter['max_token_chars'];

        while ($index < count($tokens)) {
            $token = $tokens[$index];
            if ($token === '' || strlen($token) > $maxChars) {
                $index++;

                continue;
            }

            $runEnd = $index;
            while ($runEnd + 1 < count($tokens)) {
                $gap = $words[$runEnd + 1]->start - $words[$runEnd]->end;
                if ($gap > $maxGap || $tokens[$runEnd + 1] !== $token) {
                    break;
                }
                $runEnd++;
            }

            if ($runEnd - $index + 1 >= 2) {
                for ($k = $index; $k < $runEnd; $k++) {
                    $cuts[] = $this->stutterRange($words[$k]);
                }
                $next = $runEnd + 1;
                if (
                    strlen($token) <= 3
                    && $next < count($tokens)
                    && $tokens[$next] !== $token
                    && strlen($tokens[$next]) > strlen($token)
                    && str_starts_with($tokens[$next], $token)
                    && ($words[$next]->start - $words[$runEnd]->end) <= $maxGap
                ) {
                    $cuts[] = $this->stutterRange($words[$runEnd]);
                }
                $index = $runEnd + 1;

                continue;
            }
            $index++;
        }

        return $cuts;
    }

    /**
     * @param  list<WordTimestamp>  $words
     * @return list<TimeRange>
     */
    public function findPauseCuts(array $words): array
    {
        if ($words === []) {
            return [];
        }

        $keywordParts = $this->normalizeKeywords($this->pauseKeywords);
        $tokens = array_map(static fn (WordTimestamp $w): string => self::cleanToken($w->text), $words);
        $cuts = [];
        $consumed = [];
        $silenceThreshold = (float) $this->pauseBacktrack['silence_threshold_seconds'];
        $maxSeconds = (float) $this->pauseBacktrack['max_seconds'];

        foreach ($keywordParts as $parts) {
            $n = count($parts);
            for ($i = 0; $i + $n <= count($words); $i++) {
                if (isset($consumed[$i])) {
                    continue;
                }
                if (! $this->keywordMatchesAt($tokens, $i, $parts)) {
                    continue;
                }
                $consumed[$i] = true;

                $keywordEnd = $words[$i + $n - 1]->end;
                $cutStart = $words[$i]->start;
                $origin = $cutStart;
                $walker = $i - 1;
                while ($walker >= 0) {
                    $gap = $words[$walker + 1]->start - $words[$walker]->end;
                    if ($gap >= $silenceThreshold) {
                        break;
                    }
                    if ($origin - $words[$walker]->start > $maxSeconds) {
                        break;
                    }
                    $cutStart = $words[$walker]->start;
                    $walker--;
                }
                $cuts[] = new TimeRange(max(0.0, $cutStart), $keywordEnd);
            }
        }

        return $cuts;
    }

    /**
     * @param  array<string, true>  $fillerSet
     */
    private function isFiller(string $token, array $fillerSet): bool
    {
        if ($token === '') {
            return false;
        }
        if (isset($fillerSet[$token])) {
            return true;
        }
        foreach (self::FILLER_REGEX as $pattern) {
            if (preg_match($pattern, $token) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $keywords
     * @return list<list<string>>
     */
    private function normalizeKeywords(array $keywords): array
    {
        $seen = [];
        $result = [];
        foreach ($keywords as $kw) {
            $parts = array_values(array_filter(
                array_map(
                    static fn (string $p): string => self::cleanToken($p),
                    preg_split('/\s+/', self::normalize($kw)) ?: [],
                ),
                static fn (string $p): bool => $p !== '',
            ));
            if ($parts === []) {
                continue;
            }
            $key = implode(' ', $parts);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $parts;
        }

        usort($result, static fn (array $a, array $b): int => count($b) <=> count($a));

        return $result;
    }

    /**
     * @param  list<string>  $tokens
     * @param  list<string>  $parts
     */
    private function keywordMatchesAt(array $tokens, int $start, array $parts): bool
    {
        foreach ($parts as $j => $part) {
            if (! $this->keywordPartMatches($tokens[$start + $j] ?? '', $part)) {
                return false;
            }
        }

        return true;
    }

    private function keywordPartMatches(string $token, string $part): bool
    {
        if ($token === $part) {
            return true;
        }
        if ($part === 'pausa' || $part === 'pauza') {
            return $this->looksLikePauseMarker($token);
        }

        return false;
    }

    private function looksLikePauseMarker(string $token): bool
    {
        if (in_array($token, self::PAUSE_MARKER_WORDS, true)) {
            return true;
        }

        return (str_starts_with($token, 'paus') || str_starts_with($token, 'pauz'))
            && strlen($token) <= 7;
    }

    private function stutterRange(WordTimestamp $word): TimeRange
    {
        return $this->expandToMin(max(0.0, $word->start - 0.02), $word->end + 0.05);
    }

    private function expandToMin(float $start, float $end): TimeRange
    {
        $duration = $end - $start;
        if ($duration >= $this->minSegmentSeconds) {
            return new TimeRange($start, $end);
        }
        $missing = $this->minSegmentSeconds - $duration;

        return new TimeRange(max(0.0, $start - $missing / 2), $end + $missing / 2);
    }

    private static function normalize(string $text): string
    {
        $map = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
            'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a',
            'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
            'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
            'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o',
            'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
            'Ñ' => 'n', 'Ç' => 'c',
        ];

        return mb_strtolower(strtr($text, $map));
    }

    private static function cleanToken(string $text): string
    {
        return (string) preg_replace('/[^0-9a-z]+/', '', self::normalize($text));
    }
}

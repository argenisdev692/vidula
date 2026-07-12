<?php

declare(strict_types=1);

namespace Modules\Appointment\Domain\Services;

use Modules\Appointment\Domain\ValueObjects\SpamAssessment;

/**
 * Framework-free content spam scorer for the public booking form. This is the
 * second line of defence AFTER the spatie/laravel-honeypot bot layer: honeypot
 * silently drops bots, SpamGuard scores the human-readable content of bookings
 * that pass. It never hard-blocks — it returns a {@see SpamAssessment} so the
 * caller can store the lead flagged for review (near-zero false positives by
 * design).
 *
 * Keeping this in Domain (no Laravel imports) makes the scoring rules unit
 * testable in isolation; the ServiceProvider injects the config-driven weights.
 */
final readonly class SpamGuard
{
    /**
     * @param  array<string, int>  $keywordWeights  lowercase keyword => weight
     */
    public function __construct(
        private array $keywordWeights,
        private int $threshold,
        private int $maxLinks,
    ) {}

    public function assess(string $subject, string $message): SpamAssessment
    {
        $haystack = mb_strtolower(trim($subject.' '.$message));
        $reasons = [];
        $score = 0;

        foreach ($this->keywordWeights as $keyword => $weight) {
            if ($this->containsKeyword($haystack, (string) $keyword)) {
                $score += $weight;
                $reasons[] = 'keyword:'.$keyword;
            }
        }

        $links = $this->countLinks($haystack);
        if ($links > $this->maxLinks) {
            $score += ($links - $this->maxLinks) * 2;
            $reasons[] = 'links:'.$links;
        }

        if ($this->isShouting($message)) {
            $score += 2;
            $reasons[] = 'all_caps';
        }

        if ($this->hasCharacterFlooding($message)) {
            $score += 2;
            $reasons[] = 'char_flood';
        }

        return new SpamAssessment(
            score: $score,
            isSpam: $score >= $this->threshold,
            reasons: $reasons,
        );
    }

    /**
     * Phrases (containing a space) match as a substring; single words match on
     * word boundaries so "offer" does not fire on "coffee".
     */
    private function containsKeyword(string $haystack, string $keyword): bool
    {
        if (str_contains($keyword, ' ')) {
            return str_contains($haystack, $keyword);
        }

        return preg_match('/\b'.preg_quote($keyword, '/').'\b/u', $haystack) === 1;
    }

    private function countLinks(string $haystack): int
    {
        return (int) preg_match_all('#(https?://|www\.)#i', $haystack);
    }

    /**
     * Long body written mostly in capitals — a classic shouting spam tell.
     */
    private function isShouting(string $message): bool
    {
        $letters = (string) preg_replace('/[^a-z]/i', '', $message);

        if (mb_strlen($letters) < 20) {
            return false;
        }

        $upper = (string) preg_replace('/[^A-Z]/', '', $message);

        return mb_strlen($upper) / mb_strlen($letters) > 0.7;
    }

    /**
     * The same character repeated 10+ times in a row (e.g. "!!!!!!!!!!").
     */
    private function hasCharacterFlooding(string $message): bool
    {
        return preg_match('/(.)\1{9,}/u', $message) === 1;
    }
}

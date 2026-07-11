<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Tests\Unit;

use Modules\ContactSupport\Domain\Services\SpamGuard;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for the framework-free content scorer. Uses a small explicit
 * keyword map so the assertions do not depend on config/contact_support.php.
 */
final class SpamGuardTest extends TestCase
{
    private function guard(int $threshold = 5, int $maxLinks = 1): SpamGuard
    {
        return new SpamGuard(
            keywordWeights: [
                'casino' => 4,
                'offer' => 2,
                'special offer' => 3,
                'crypto' => 3,
            ],
            threshold: $threshold,
            maxLinks: $maxLinks,
        );
    }

    public function test_a_normal_support_message_scores_zero_and_is_not_spam(): void
    {
        $assessment = $this->guard()->assess('Help with my account', 'I cannot log in and need assistance.');

        $this->assertSame(0, $assessment->score);
        $this->assertFalse($assessment->isSpam);
        $this->assertSame([], $assessment->reasons);
    }

    public function test_help_is_not_a_spam_keyword(): void
    {
        $assessment = $this->guard()->assess('Need help', 'help help help help help');

        $this->assertFalse($assessment->isSpam);
    }

    public function test_keyword_weights_accumulate_and_cross_the_threshold(): void
    {
        $assessment = $this->guard()->assess('Special offer', 'Join our casino today.');

        // special offer (3) + offer (2) + casino (4) = 9 >= 5
        $this->assertGreaterThanOrEqual(5, $assessment->score);
        $this->assertTrue($assessment->isSpam);
        $this->assertContains('keyword:casino', $assessment->reasons);
    }

    public function test_word_boundaries_prevent_false_positives(): void
    {
        // "coffee" must not trip the "offer" keyword.
        $assessment = $this->guard()->assess('Coffee order', 'I would like to order some coffee.');

        $this->assertSame(0, $assessment->score);
    }

    public function test_excess_links_add_to_the_score(): void
    {
        $assessment = $this->guard(threshold: 4, maxLinks: 1)
            ->assess('Hi', 'See http://a.com http://b.com and www.c.com');

        // 3 links, 2 over the max → 2 * 2 = 4 >= 4
        $this->assertTrue($assessment->isSpam);
        $this->assertContains('links:3', $assessment->reasons);
    }

    public function test_shouting_body_is_penalised(): void
    {
        $assessment = $this->guard()->assess('HELLO', 'BUY THIS PRODUCT RIGHT NOW IT IS AMAZING');

        $this->assertContains('all_caps', $assessment->reasons);
    }

    public function test_character_flooding_is_penalised(): void
    {
        $assessment = $this->guard()->assess('Hi', 'Wowwwwwwwwwwww!!!!!!!!!!');

        $this->assertContains('char_flood', $assessment->reasons);
    }
}

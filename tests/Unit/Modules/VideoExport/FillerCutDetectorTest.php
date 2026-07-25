<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\VideoExport;

use Modules\VideoExport\Domain\Services\FillerCutDetector;
use Modules\VideoExport\Domain\ValueObjects\WordTimestamp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FillerCutDetectorTest extends TestCase
{
    private FillerCutDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new FillerCutDetector(
            fillerTerms: ['eh', 'em', 'este', 'pues'],
            pauseKeywords: ['PAUSA ACA', 'PAUSA'],
            pauseBacktrack: [
                'silence_threshold_seconds' => 0.4,
                'max_seconds' => 8.0,
            ],
            stutter: [
                'max_gap_seconds' => 0.4,
                'max_token_chars' => 5,
            ],
            minSegmentSeconds: 0.25,
        );
    }

    #[Test]
    public function it_cuts_filler_words_including_regex_variants(): void
    {
        $words = [
            new WordTimestamp('Hola', 0.0, 0.5),
            new WordTimestamp('eh', 0.6, 0.8),
            new WordTimestamp('mundo', 1.0, 1.5),
            new WordTimestamp('ehhh', 2.0, 2.3),
        ];

        $cuts = $this->detector->findFillerCuts($words);

        $this->assertCount(2, $cuts);
    }

    #[Test]
    public function it_removes_stutter_run_keeping_last(): void
    {
        $words = [
            new WordTimestamp('y', 0.0, 0.2),
            new WordTimestamp('y', 0.3, 0.5),
            new WordTimestamp('y', 0.6, 0.8),
            new WordTimestamp('vamos', 1.0, 1.5),
        ];

        $cuts = $this->detector->findStutterCuts($words);

        $this->assertCount(2, $cuts);
    }

    #[Test]
    public function it_detects_pausa_and_backtracks(): void
    {
        $words = [
            new WordTimestamp('bueno', 0.0, 0.4),
            new WordTimestamp('esto', 0.5, 0.9),
            new WordTimestamp('pausa', 1.0, 1.4),
            new WordTimestamp('ahora', 3.0, 3.4),
        ];

        $cuts = $this->detector->findPauseCuts($words);

        $this->assertCount(1, $cuts);
        $this->assertEqualsWithDelta(1.4, $cuts[0]->end, 0.001);
        $this->assertLessThan(1.0, $cuts[0]->start);
    }
}

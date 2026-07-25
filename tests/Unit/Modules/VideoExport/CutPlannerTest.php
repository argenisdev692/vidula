<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\VideoExport;

use Modules\VideoExport\Domain\Services\CutPlanner;
use Modules\VideoExport\Domain\ValueObjects\TimeRange;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CutPlannerTest extends TestCase
{
    private CutPlanner $planner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->planner = new CutPlanner(0.25);
    }

    #[Test]
    public function it_inverts_a_single_mid_cut_into_two_keep_ranges(): void
    {
        $keep = $this->planner->invertCuts([new TimeRange(10.0, 12.0)], 20.0);

        $this->assertCount(2, $keep);
        $this->assertSame(0.0, $keep[0]->start);
        $this->assertSame(10.0, $keep[0]->end);
        $this->assertSame(12.0, $keep[1]->start);
        $this->assertSame(20.0, $keep[1]->end);
        $this->assertSame(18.0, $this->planner->totalDuration($keep));
    }

    #[Test]
    public function it_merges_overlapping_cuts(): void
    {
        $keep = $this->planner->invertCuts([
            new TimeRange(5.0, 9.0),
            new TimeRange(8.0, 12.0),
        ], 20.0);

        $this->assertCount(2, $keep);
        $this->assertSame(0.0, $keep[0]->start);
        $this->assertSame(5.0, $keep[0]->end);
        $this->assertSame(12.0, $keep[1]->start);
        $this->assertSame(20.0, $keep[1]->end);
    }

    #[Test]
    public function it_falls_back_to_full_clip_when_everything_is_cut(): void
    {
        $keep = $this->planner->invertCuts([new TimeRange(0.0, 20.0)], 20.0);

        $this->assertCount(1, $keep);
        $this->assertSame(0.0, $keep[0]->start);
        $this->assertSame(20.0, $keep[0]->end);
    }
}
